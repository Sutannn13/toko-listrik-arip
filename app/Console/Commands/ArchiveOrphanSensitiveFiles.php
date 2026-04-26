<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ArchiveOrphanSensitiveFiles extends Command
{
    protected $signature = 'security:archive-orphan-sensitive-files
        {--dry-run : Scan only without moving files into private archive}';

    protected $description = 'Archive orphan sensitive files from public storage into private local storage.';

    /**
     * @var array<string, array{folder: string, table: string, column: string}>
     */
    private array $definitions = [
        'payments' => [
            'folder' => 'payments',
            'table' => 'payments',
            'column' => 'proof_url',
        ],
        'warranty-claims' => [
            'folder' => 'warranty-claims',
            'table' => 'warranty_claims',
            'column' => 'damage_proof_url',
        ],
        'profile-photos' => [
            'folder' => 'profile-photos',
            'table' => 'users',
            'column' => 'profile_photo_path',
        ],
    ];

    /**
     * @var array<string, int>
     */
    private array $totals = [
        'public_files' => 0,
        'referenced' => 0,
        'orphan' => 0,
        'archived' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $archiveDate = now()->format('Ymd');

        $this->info($dryRun
            ? 'DRY-RUN: scan orphan sensitive public files tanpa memindahkan file.'
            : 'REAL RUN: pindahkan orphan sensitive public files ke private archive.');
        $this->line("Archive target: storage/app/private/archive/orphan-sensitive-files/{$archiveDate}/");

        foreach ($this->definitions as $name => $definition) {
            $this->newLine();
            $this->line("Memproses {$name}...");
            $stats = $this->processFolder($definition, $archiveDate, $dryRun);

            foreach ($stats as $key => $value) {
                $this->totals[$key] += $value;
            }

            $this->line("Ringkasan {$name}: public={$stats['public_files']}, referenced={$stats['referenced']}, orphan={$stats['orphan']}, archived={$stats['archived']}, skipped={$stats['skipped']}, failed={$stats['failed']}");
        }

        $this->newLine();
        $this->info('Ringkasan archive orphan sensitive files:');
        $this->line('total file public: ' . $this->totals['public_files']);
        $this->line('total referenced: ' . $this->totals['referenced']);
        $this->line('total orphan: ' . $this->totals['orphan']);
        $this->line('total archived: ' . $this->totals['archived']);
        $this->line('total skipped: ' . $this->totals['skipped']);
        $this->line('total failed: ' . $this->totals['failed']);

        return Command::SUCCESS;
    }

    /**
     * @param array{folder: string, table: string, column: string} $definition
     * @return array<string, int>
     */
    private function processFolder(array $definition, string $archiveDate, bool $dryRun): array
    {
        $folder = $definition['folder'];
        $stats = [
            'public_files' => 0,
            'referenced' => 0,
            'orphan' => 0,
            'archived' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $publicFiles = collect(Storage::disk('public')->allFiles($folder))
            ->map(fn(string $path): string => str_replace('\\', '/', $path))
            ->values();
        $references = $this->referencedPaths($definition);

        $stats['public_files'] = $publicFiles->count();

        foreach ($publicFiles as $publicPath) {
            $normalizedPath = $this->normalizePublicFilePath($publicPath, $folder);

            if ($normalizedPath === null) {
                $stats['failed']++;
                $this->warn("[REJECTED] {$publicPath}");

                continue;
            }

            if ($references->contains($normalizedPath)) {
                $stats['referenced']++;
                $this->line("[REFERENCED] {$normalizedPath}");

                continue;
            }

            $stats['orphan']++;
            $archivePath = "archive/orphan-sensitive-files/{$archiveDate}/{$normalizedPath}";
            $this->line(($dryRun ? '[DRY-RUN]' : '[ARCHIVE]') . " {$normalizedPath} -> {$archivePath}");

            if ($dryRun) {
                continue;
            }

            $result = $this->archivePublicFile($normalizedPath, $archivePath);
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * @param array{folder: string, table: string, column: string} $definition
     * @return Collection<int, string>
     */
    private function referencedPaths(array $definition): Collection
    {
        $folder = $definition['folder'];

        return DB::table($definition['table'])
            ->whereNotNull($definition['column'])
            ->where($definition['column'], '!=', '')
            ->pluck($definition['column'])
            ->map(fn(?string $path): ?string => $this->normalizeReferencePath($path, $folder))
            ->filter()
            ->unique()
            ->values();
    }

    private function archivePublicFile(string $publicPath, string $archivePath): string
    {
        try {
            $sourceSize = $this->diskSize('public', $publicPath);

            if (Storage::disk('local')->exists($archivePath)) {
                if ($this->diskSize('local', $archivePath) !== $sourceSize) {
                    $this->warn("[CONFLICT] archive target sudah ada dengan ukuran beda: {$archivePath}");

                    return 'failed';
                }

                if (Storage::disk('public')->delete($publicPath)) {
                    $this->line("[ARCHIVED EXISTING] {$publicPath}");

                    return 'archived';
                }

                $this->error("[FAILED] gagal menghapus public source setelah archive existing: {$publicPath}");

                return 'failed';
            }

            $contents = Storage::disk('public')->get($publicPath);
            if (! Storage::disk('local')->put($archivePath, $contents)) {
                $this->error("[FAILED] gagal menulis archive: {$archivePath}");

                return 'failed';
            }

            if (! Storage::disk('local')->exists($archivePath)) {
                $this->error("[FAILED] archive tidak ditemukan setelah copy: {$archivePath}");

                return 'failed';
            }

            if ($this->diskSize('local', $archivePath) !== $sourceSize) {
                Storage::disk('local')->delete($archivePath);
                $this->error("[FAILED] ukuran archive tidak sama: {$archivePath}");

                return 'failed';
            }

            if (! Storage::disk('public')->delete($publicPath)) {
                $this->error("[FAILED] gagal menghapus public source setelah archive: {$publicPath}");

                return 'failed';
            }

            return 'archived';
        } catch (Throwable $exception) {
            report($exception);
            $this->error("[FAILED] {$publicPath}: {$exception->getMessage()}");

            return 'failed';
        }
    }

    private function normalizeReferencePath(?string $path, string $folder): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));

        if ($path === '' || str_starts_with($path, '//') || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            return null;
        }

        $path = preg_replace('#^storage/app/public/#', '', $path) ?? $path;
        $path = preg_replace('#^app/public/#', '', $path) ?? $path;
        $path = preg_replace('#^public/#', '', $path) ?? $path;
        $path = preg_replace('#^storage/#', '', $path) ?? $path;
        $path = ltrim($path, '/');

        if (! str_starts_with($path, $folder . '/')) {
            return null;
        }

        return $this->normalizePublicFilePath($path, $folder);
    }

    private function normalizePublicFilePath(string $path, string $folder): ?string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:/#', $path)) {
            return null;
        }

        $segments = explode('/', $path);
        if (in_array('..', $segments, true)) {
            return null;
        }

        if (! str_starts_with($path, $folder . '/') || $path === $folder . '/') {
            return null;
        }

        return $path;
    }

    private function diskSize(string $disk, string $path): int
    {
        return (int) Storage::disk($disk)->size($path);
    }
}
