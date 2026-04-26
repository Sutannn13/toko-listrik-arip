<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArchiveOrphanSensitiveFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_lists_orphans_without_moving_anything(): void
    {
        $this->fakeSensitiveDisks();

        Storage::disk('public')->put('payments/orphan-proof.jpg', 'orphan');

        $this->artisan('security:archive-orphan-sensitive-files', [
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('[DRY-RUN] payments/orphan-proof.jpg')
            ->expectsOutputToContain('total file public: 1')
            ->expectsOutputToContain('total orphan: 1')
            ->expectsOutputToContain('total archived: 0')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('payments/orphan-proof.jpg'));
        $this->assertFalse(Storage::disk('local')->exists($this->archivePath('payments/orphan-proof.jpg')));
    }

    public function test_real_run_archives_only_orphan_files_and_keeps_referenced_public_files(): void
    {
        $this->fakeSensitiveDisks();

        $payment = $this->createPaymentWithProof('payments/referenced-proof.jpg');
        Storage::disk('public')->put((string) $payment->proof_url, 'referenced');
        Storage::disk('public')->put('payments/orphan-proof.jpg', 'orphan');
        Storage::disk('public')->put('profile-photos/orphan-avatar.jpg', 'avatar');

        $this->artisan('security:archive-orphan-sensitive-files')
            ->expectsOutputToContain('[REFERENCED] payments/referenced-proof.jpg')
            ->expectsOutputToContain('[ARCHIVE] payments/orphan-proof.jpg')
            ->expectsOutputToContain('[ARCHIVE] profile-photos/orphan-avatar.jpg')
            ->expectsOutputToContain('total file public: 3')
            ->expectsOutputToContain('total referenced: 1')
            ->expectsOutputToContain('total orphan: 2')
            ->expectsOutputToContain('total archived: 2')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('payments/referenced-proof.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('payments/orphan-proof.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('profile-photos/orphan-avatar.jpg'));
        $this->assertSame('orphan', Storage::disk('local')->get($this->archivePath('payments/orphan-proof.jpg')));
        $this->assertSame('avatar', Storage::disk('local')->get($this->archivePath('profile-photos/orphan-avatar.jpg')));
    }

    public function test_archive_conflict_with_different_size_is_failed_and_public_file_stays(): void
    {
        $this->fakeSensitiveDisks();

        Storage::disk('public')->put('profile-photos/conflict.jpg', 'public-file');
        Storage::disk('local')->put($this->archivePath('profile-photos/conflict.jpg'), 'different-private-archive');

        $this->artisan('security:archive-orphan-sensitive-files')
            ->expectsOutputToContain('[CONFLICT] archive target sudah ada dengan ukuran beda')
            ->expectsOutputToContain('total failed: 1')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('profile-photos/conflict.jpg'));
        $this->assertSame('different-private-archive', Storage::disk('local')->get($this->archivePath('profile-photos/conflict.jpg')));
    }

    private function createPaymentWithProof(string $proofPath): Payment
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'ORD-' . Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081200000001',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 100000,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'placed_at' => now(),
        ]);

        return Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-' . Str::upper(Str::random(10)),
            'method' => 'bank_transfer',
            'amount' => 100000,
            'status' => 'pending',
            'proof_url' => $proofPath,
        ]);
    }

    private function archivePath(string $publicPath): string
    {
        return 'archive/orphan-sensitive-files/' . now()->format('Ymd') . '/' . $publicPath;
    }

    private function fakeSensitiveDisks(): void
    {
        Storage::fake('local');
        Storage::fake('public');
    }
}
