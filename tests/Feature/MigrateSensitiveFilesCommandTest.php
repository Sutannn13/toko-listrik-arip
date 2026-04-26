<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MigrateSensitiveFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_only_scans_without_copying_updating_or_deleting(): void
    {
        $this->fakeSensitiveDisks();

        $payment = $this->createPaymentWithProof('payments/ORD-DRY/proof.jpg');
        Storage::disk('public')->put('payments/ORD-DRY/proof.jpg', 'proof-data');

        $this->artisan('security:migrate-sensitive-files', [
            '--dry-run' => true,
            '--type' => 'payments',
        ])
            ->expectsOutputToContain('[DRY-RUN] payments #' . $payment->id)
            ->expectsOutputToContain('total kandidat migrasi: 1')
            ->expectsOutputToContain('total berhasil dicopy: 0')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('payments/ORD-DRY/proof.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('payments/ORD-DRY/proof.jpg'));
        $this->assertSame('payments/ORD-DRY/proof.jpg', $payment->fresh()->proof_url);
    }

    public function test_real_run_copies_to_private_updates_normalized_path_and_keeps_public_by_default(): void
    {
        $this->fakeSensitiveDisks();

        $payment = $this->createPaymentWithProof('storage/payments/ORD-NORM/proof.jpg');
        Storage::disk('public')->put('payments/ORD-NORM/proof.jpg', 'normalized-proof-data');

        $this->artisan('security:migrate-sensitive-files', [
            '--type' => 'payments',
        ])
            ->expectsOutputToContain('[COPY] payments #' . $payment->id)
            ->expectsOutputToContain('[DB] payments #' . $payment->id)
            ->expectsOutputToContain('total berhasil dicopy: 1')
            ->expectsOutputToContain('total DB updated: 1')
            ->expectsOutputToContain('total public deleted: 0')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('payments/ORD-NORM/proof.jpg'));
        $this->assertTrue(Storage::disk('local')->exists('payments/ORD-NORM/proof.jpg'));
        $this->assertSame('payments/ORD-NORM/proof.jpg', $payment->fresh()->proof_url);
    }

    public function test_delete_public_flag_removes_public_file_after_verified_copy(): void
    {
        $this->fakeSensitiveDisks();

        $payment = $this->createPaymentWithProof('payments/ORD-DELETE/proof.jpg');
        Storage::disk('public')->put('payments/ORD-DELETE/proof.jpg', 'delete-after-copy');

        $this->artisan('security:migrate-sensitive-files', [
            '--type' => 'payments',
            '--delete-public' => true,
        ])
            ->expectsOutputToContain('[DELETE PUBLIC] payments #' . $payment->id)
            ->expectsOutputToContain('total berhasil dicopy: 1')
            ->expectsOutputToContain('total public deleted: 1')
            ->assertSuccessful();

        $this->assertFalse(Storage::disk('public')->exists('payments/ORD-DELETE/proof.jpg'));
        $this->assertTrue(Storage::disk('local')->exists('payments/ORD-DELETE/proof.jpg'));
    }

    public function test_type_option_limits_migration_scope(): void
    {
        $this->fakeSensitiveDisks();

        $payment = $this->createPaymentWithProof('payments/ORD-TYPE/proof.jpg');
        $claim = $this->createWarrantyClaimWithProof('warranty-claims/WRN-TYPE/proof.jpg');
        Storage::disk('public')->put((string) $payment->proof_url, 'payment-proof');
        Storage::disk('public')->put((string) $claim->damage_proof_url, 'claim-proof');

        $this->artisan('security:migrate-sensitive-files', [
            '--type' => 'payments',
        ])
            ->expectsOutputToContain('Memproses payments')
            ->doesntExpectOutputToContain('Memproses warranty-claims')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('local')->exists((string) $payment->proof_url));
        $this->assertFalse(Storage::disk('local')->exists((string) $claim->damage_proof_url));
        $this->assertTrue(Storage::disk('public')->exists((string) $claim->damage_proof_url));
    }

    public function test_private_conflict_with_different_size_is_skipped_without_overwrite(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create([
            'profile_photo_path' => 'profile-photos/conflict.jpg',
        ]);
        Storage::disk('public')->put('profile-photos/conflict.jpg', 'public-file');
        Storage::disk('local')->put('profile-photos/conflict.jpg', 'private-file-with-different-size');

        $this->artisan('security:migrate-sensitive-files', [
            '--type' => 'profile-photos',
        ])
            ->expectsOutputToContain('[CONFLICT] profile-photos #' . $user->id)
            ->expectsOutputToContain('total gagal: 1')
            ->assertSuccessful();

        $this->assertSame('private-file-with-different-size', Storage::disk('local')->get('profile-photos/conflict.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('profile-photos/conflict.jpg'));
    }

    public function test_rejects_unsafe_or_unexpected_paths(): void
    {
        $this->fakeSensitiveDisks();

        $externalUrlUser = User::factory()->create([
            'profile_photo_path' => 'https://example.test/profile.jpg',
        ]);
        $wrongFolderUser = User::factory()->create([
            'profile_photo_path' => 'products/public-product.jpg',
        ]);

        $this->artisan('security:migrate-sensitive-files', [
            '--type' => 'profile-photos',
        ])
            ->expectsOutputToContain('[REJECTED] profile-photos #' . $externalUrlUser->id)
            ->expectsOutputToContain('[REJECTED] profile-photos #' . $wrongFolderUser->id)
            ->expectsOutputToContain('total gagal: 2')
            ->assertSuccessful();
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

    private function createWarrantyClaimWithProof(string $proofPath): WarrantyClaim
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_code' => 'ORD-' . Str::upper(Str::random(10)),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081200000002',
            'status' => 'completed',
            'payment_status' => 'paid',
            'warranty_status' => 'active',
            'subtotal' => 150000,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => 150000,
            'placed_at' => now(),
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Produk Garansi',
            'product_slug' => 'produk-garansi',
            'unit' => 'pcs',
            'price' => 150000,
            'quantity' => 1,
            'subtotal' => 150000,
            'warranty_days' => 30,
            'warranty_expires_at' => now()->addDays(30),
        ]);

        return WarrantyClaim::create([
            'claim_code' => 'WRN-' . Str::upper(Str::random(10)),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $user->id,
            'reason' => 'Produk tidak berfungsi normal.',
            'status' => 'submitted',
            'damage_proof_url' => $proofPath,
            'damage_proof_mime' => 'image/jpeg',
            'requested_at' => now(),
        ]);
    }

    private function fakeSensitiveDisks(): void
    {
        Storage::fake('local');
        Storage::fake('public');
    }
}
