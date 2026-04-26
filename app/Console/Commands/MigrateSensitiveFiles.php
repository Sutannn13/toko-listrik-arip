<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateSensitiveFiles extends Command
{
    protected $signature = 'security:migrate-sensitive-files
        {--dry-run : Scan only without copying, updating database, or deleting public files}
        {--delete-public : Delete public source files after verified private copy}
        {--type= : Limit to payments, warranty-claims, or profile-photos}';

    protected $description = 'Migrate legacy sensitive uploads from public storage to private local storage.';

    /**
     * @var array<string, array{model: class-string<Model>, column: string, prefix: string, label: string}>
     */
    private array $definitions = [
        'payments' => [
            'model' => Payment::class,
            'column' => 'proof_url',
            'prefix' => 'payments/',
            'label' => 'bukti bayar',
        ],
        'warranty-claims' => [
            'model' => WarrantyClaim::class,
            'column' => 'damage_proof_url',
            'prefix' => 'warranty-claims/',
            'label' => 'bukti klaim garansi',
        ],
        'profile-photos' => [
            'model' => User::class,
            'column' => 'profile_photo_path',
            'prefix' => 'profile-photos/',
            'label' => 'foto profil',
        ],
    ];

    /**
     * @var array<string, int>
     */
    private array $stats = [
        'found' => 0,
        'candidates' => 0,
        'already_private' => 0,
        'copied' => 0,
        'db_updated' => 0,
        'failed' => 0,
        'skipped' => 0,
        'public_deleted' => 0,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deletePublic = (bool) $this->option('delete-public');
        $type = $this->option('type');

        if ($type !== null && $type !== '' && ! array_key_exists($type, $this->definitions)) {
            $this->error('Type tidak valid. Gunakan: payments, warranty-claims, atau profile-photos.');

            return Command::FAILURE;
        }

        if ($dryRun && $deletePublic) {
            $this->warn('--dry-run aktif, flag --delete-public diabaikan.');
        }

        $definitions = $type ? [$type => $this->definitions[$type]] : $this->definitions;

        $this->info($dryRun
            ? 'DRY-RUN: scan file sensitif lama tanpa copy, update DB, atau delete.'
            : 'REAL RUN: copy file public ke private/local dan verifikasi ukuran.');

        if (! $dryRun && ! $deletePublic) {
            $this->line('Catatan: file public lama tidak akan dihapus tanpa flag --delete-public.');
        }

        foreach ($definitions as $key => $definition) {
            $this->newLine();
            $this->line("Memproses {$key} ({$definition['label']})...");
            $this->processDefinition($key, $definition, $dryRun, $deletePublic);
        }

        $this->newLine();
        $this->info('Ringkasan migrasi file sensitif:');
        $this->line('total ditemukan: ' . $this->stats['found']);
        $this->line('total kandidat migrasi: ' . $this->stats['candidates']);
        $this->line('total sudah ada di private: ' . $this->stats['already_private']);
        $this->line('total berhasil dicopy: ' . $this->stats['copied']);
        $this->line('total DB updated: ' . $this->stats['db_updated']);
        $this->line('total gagal: ' . $this->stats['failed']);
        $this->line('total dilewati: ' . $this->stats['skipped']);
        $this->line('total public deleted: ' . $this->stats['public_deleted']);

        return Command::SUCCESS;
    }

    /**
     * @param array{model: class-string<Model>, column: string, prefix: string, label: string} $definition
     */
    private function processDefinition(string $type, array $definition, bool $dryRun, bool $deletePublic): void
    {
        $modelClass = $definition['model'];
        $column = $definition['column'];
        $prefix = $definition['prefix'];

        $modelClass::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($type, $column, $prefix, $dryRun, $deletePublic) {
                foreach ($records as $record) {
                    $this->stats['found']++;
                    $this->processRecord($type, $record, $column, $prefix, $dryRun, $deletePublic);
                }
            });
    }

    private function processRecord(string $type, Model $record, string $column, string $prefix, bool $dryRun, bool $deletePublic): void
    {
        $rawPath = (string) $record->getAttribute($column);
        $normalizedPath = $this->normalizePath($rawPath, $prefix);
        $recordLabel = "{$type} #{$record->getKey()}";

        if ($normalizedPath === null) {
            $this->stats['failed']++;
            $this->warn("[REJECTED] {$recordLabel}: path ditolak ({$rawPath})");

            return;
        }

        $publicExists = Storage::disk('public')->exists($normalizedPath);
        $privateExists = Storage::disk('local')->exists($normalizedPath);

        if ($privateExists) {
            if ($publicExists && $this->diskSize('public', $normalizedPath) !== $this->diskSize('local', $normalizedPath)) {
                $this->stats['failed']++;
                $this->warn("[CONFLICT] {$recordLabel}: private sudah ada tapi ukuran beda ({$normalizedPath})");

                return;
            }

            $this->stats['already_private']++;
            $this->line("[PRIVATE] {$recordLabel}: {$normalizedPath}");

            if (! $this->updateDatabasePathIfNeeded($record, $column, $rawPath, $normalizedPath, $dryRun, $recordLabel)) {
                return;
            }

            $this->deletePublicIfRequested($normalizedPath, $dryRun, $deletePublic, $recordLabel);

            return;
        }

        if (! $publicExists) {
            $this->stats['skipped']++;
            $this->line("[SKIP] {$recordLabel}: file public/private tidak ditemukan ({$normalizedPath})");

            return;
        }

        $this->stats['candidates']++;
        $this->line(($dryRun ? '[DRY-RUN]' : '[COPY]') . " {$recordLabel}: public:{$normalizedPath} -> local:{$normalizedPath}");

        if ($dryRun) {
            return;
        }

        if (! $this->copyAndVerify($normalizedPath, $recordLabel)) {
            return;
        }

        if (! $this->updateDatabasePathIfNeeded($record, $column, $rawPath, $normalizedPath, false, $recordLabel)) {
            return;
        }

        $this->deletePublicIfRequested($normalizedPath, false, $deletePublic, $recordLabel);
    }

    private function normalizePath(string $rawPath, string $requiredPrefix): ?string
    {
        $path = str_replace('\\', '/', trim($rawPath));

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (str_starts_with($path, '//') || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            return null;
        }

        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:/#', $path)) {
            return null;
        }

        $path = preg_replace('#^storage/app/public/#', '', $path) ?? $path;
        $path = preg_replace('#^app/public/#', '', $path) ?? $path;
        $path = preg_replace('#^public/#', '', $path) ?? $path;
        $path = preg_replace('#^storage/#', '', $path) ?? $path;
        $path = ltrim($path, '/');

        $segments = explode('/', $path);
        if (in_array('..', $segments, true)) {
            return null;
        }

        if (! str_starts_with($path, $requiredPrefix) || $path === $requiredPrefix) {
            return null;
        }

        return $path;
    }

    private function copyAndVerify(string $path, string $recordLabel): bool
    {
        try {
            $sourceSize = $this->diskSize('public', $path);
            $contents = Storage::disk('public')->get($path);

            if (! Storage::disk('local')->put($path, $contents)) {
                $this->stats['failed']++;
                $this->error("[FAILED] {$recordLabel}: gagal copy ke local ({$path})");

                return false;
            }

            if (! Storage::disk('local')->exists($path)) {
                $this->stats['failed']++;
                $this->error("[FAILED] {$recordLabel}: file local tidak ditemukan setelah copy ({$path})");

                return false;
            }

            $targetSize = $this->diskSize('local', $path);
            if ($sourceSize !== $targetSize) {
                Storage::disk('local')->delete($path);
                $this->stats['failed']++;
                $this->error("[FAILED] {$recordLabel}: ukuran file tidak sama setelah copy ({$path})");

                return false;
            }

            $this->stats['copied']++;

            return true;
        } catch (Throwable $exception) {
            report($exception);

            $this->stats['failed']++;
            $this->error("[FAILED] {$recordLabel}: {$exception->getMessage()}");

            return false;
        }
    }

    private function updateDatabasePathIfNeeded(Model $record, string $column, string $rawPath, string $normalizedPath, bool $dryRun, string $recordLabel): bool
    {
        if ($rawPath === $normalizedPath) {
            return true;
        }

        $this->line(($dryRun ? '[DRY-RUN DB]' : '[DB]') . " {$recordLabel}: {$column} {$rawPath} -> {$normalizedPath}");

        if ($dryRun) {
            return true;
        }

        try {
            $record->forceFill([$column => $normalizedPath])->saveQuietly();
            $this->stats['db_updated']++;

            return true;
        } catch (Throwable $exception) {
            report($exception);

            $this->stats['failed']++;
            $this->error("[FAILED] {$recordLabel}: gagal update DB ({$exception->getMessage()})");

            return false;
        }
    }

    private function deletePublicIfRequested(string $path, bool $dryRun, bool $deletePublic, string $recordLabel): void
    {
        if (! $deletePublic || $dryRun || ! Storage::disk('public')->exists($path)) {
            return;
        }

        if (! Storage::disk('local')->exists($path)) {
            $this->stats['failed']++;
            $this->error("[FAILED] {$recordLabel}: batal delete public karena file local tidak ada ({$path})");

            return;
        }

        if ($this->diskSize('public', $path) !== $this->diskSize('local', $path)) {
            $this->stats['failed']++;
            $this->error("[FAILED] {$recordLabel}: batal delete public karena ukuran file beda ({$path})");

            return;
        }

        if (Storage::disk('public')->delete($path)) {
            $this->stats['public_deleted']++;
            $this->line("[DELETE PUBLIC] {$recordLabel}: {$path}");

            return;
        }

        $this->stats['failed']++;
        $this->error("[FAILED] {$recordLabel}: gagal delete file public ({$path})");
    }

    private function diskSize(string $disk, string $path): int
    {
        return (int) Storage::disk($disk)->size($path);
    }
}
