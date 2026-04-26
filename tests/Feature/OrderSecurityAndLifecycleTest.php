<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Notifications\PaymentProofStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderSecurityAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_upload_payment_proof_for_another_users_order(): void
    {
        $this->fakeSensitiveDisks();

        $orderOwner = User::factory()->create();
        $otherUser = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'MCB Secure',
            'slug' => 'mcb-secure',
            'price' => 45000,
            'stock' => 8,
        ]);

        [$order, $payment] = $this->createPendingOrder($orderOwner, $product, 1, now()->subMinutes(20));

        $response = $this->actingAs($otherUser)
            ->from(route('home.tracking'))
            ->post(route('home.tracking.proof', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('proof.png'),
            ]);

        $response->assertNotFound();

        $payment->refresh();
        $this->assertNull($payment->proof_url);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_order_owner_can_upload_payment_proof_for_own_order(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Kabel Aman',
            'slug' => 'kabel-aman',
            'price' => 120000,
            'stock' => 9,
        ]);

        [$order, $payment] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(10));

        $response = $this->actingAs($user)
            ->from(route('home.tracking'))
            ->post(route('home.tracking.proof', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('proof-owner.jpg'),
            ]);

        $response->assertRedirect(route('home.tracking'));
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertNotNull($payment->proof_url);
        $this->assertStringStartsWith('payments/' . $order->order_code . '/', (string) $payment->proof_url);
        $this->assertStringNotContainsString('proof-owner', (string) $payment->proof_url);
        $this->assertTrue(Storage::disk('local')->exists((string) $payment->proof_url));
        $this->assertFalse(Storage::disk('public')->exists((string) $payment->proof_url));
    }

    public function test_payment_proof_endpoint_allows_owner_and_admin_only(): void
    {
        $this->fakeSensitiveDisks();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = $this->createAdminUser();
        $product = $this->createProduct([
            'name' => 'Lampu Proof Endpoint',
            'slug' => 'lampu-proof-endpoint',
            'price' => 72000,
            'stock' => 5,
        ]);

        [$order, $payment] = $this->createPendingOrder($owner, $product, 1, now()->subMinutes(10));
        $proofPath = UploadedFile::fake()->image('proof-private.jpg')->store('payments/' . $order->order_code, 'local');
        $payment->update(['proof_url' => $proofPath]);

        $route = route('home.tracking.proof.view', [
            'orderCode' => $order->order_code,
            'payment' => $payment,
        ]);

        $ownerResponse = $this->actingAs($owner)->get($route);
        $ownerResponse->assertOk();
        $this->assertSame('image/jpeg', $ownerResponse->headers->get('content-type'));

        $this->actingAs($admin)->get($route)->assertOk();
        $this->actingAs($otherUser)->get($route)->assertForbidden();
    }

    public function test_legacy_public_payment_proof_is_still_served_through_protected_endpoint(): void
    {
        $this->fakeSensitiveDisks();

        $owner = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Lampu Legacy Proof',
            'slug' => 'lampu-legacy-proof',
            'price' => 82000,
            'stock' => 5,
        ]);

        [$order, $payment] = $this->createPendingOrder($owner, $product, 1, now()->subMinutes(10));
        $legacyProofPath = UploadedFile::fake()->image('legacy-proof.jpg')->store('payments/' . $order->order_code, 'public');
        $payment->update(['proof_url' => $legacyProofPath]);

        $response = $this->actingAs($owner)->get(route('home.tracking.proof.view', [
            'orderCode' => $order->order_code,
            'payment' => $payment,
        ]));

        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('content-type'));
    }

    public function test_order_with_cod_method_cannot_upload_payment_proof(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Stop Kontak COD',
            'slug' => 'stop-kontak-cod',
            'price' => 55000,
            'stock' => 7,
        ]);

        [$order, $payment] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(10));
        $payment->update([
            'method' => 'cod',
            'notes' => 'COD test payment.',
        ]);

        $response = $this->actingAs($user)
            ->from(route('home.tracking'))
            ->post(route('home.tracking.proof', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('proof-cod.jpg'),
            ]);

        $response->assertRedirect(route('home.tracking'));
        $response->assertSessionHas('error');

        $payment->refresh();
        $this->assertNull($payment->proof_url);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_order_owner_can_open_tracking_detail_without_manual_code_form(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Lampu Tracking Detail',
            'slug' => 'lampu-tracking-detail',
            'price' => 65000,
            'stock' => 8,
        ]);

        [$order] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(15));

        $response = $this->actingAs($user)
            ->get(route('home.tracking.show', $order->order_code));

        $response->assertOk();
        $response->assertSee($order->order_code);
        $response->assertSee('Kode Pesanan');
    }

    public function test_tracking_check_form_redirects_to_new_tracking_detail_page(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Kabel Redirect Tracking',
            'slug' => 'kabel-redirect-tracking',
            'price' => 34000,
            'stock' => 10,
        ]);

        [$order] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(20));

        $response = $this->actingAs($user)
            ->post(route('home.tracking.check'), [
                'order_code' => $order->order_code,
            ]);

        $response->assertRedirect(route('home.tracking.show', $order->order_code));
    }

    public function test_admin_can_approve_uploaded_payment_proof_and_mark_order_paid(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'MCB Approval Flow',
            'slug' => 'mcb-approval-flow',
            'price' => 150000,
            'stock' => 5,
        ]);

        [$order, $payment] = $this->createPendingOrder($customer, $product, 1, now()->subMinutes(30));

        $payment->update([
            'proof_url' => 'payments/' . $order->order_code . '/proof-approve.jpg',
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.payments.approve', [$order, $payment]), [
                'admin_notes' => 'Nominal dan rekening tujuan sesuai mutasi.',
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $payment->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertStringContainsString('Bukti pembayaran diverifikasi admin', (string) $payment->notes);

        $notification = $customer->fresh()->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame(PaymentProofStatusUpdatedNotification::class, $notification->type);
        $this->assertSame('approved', $notification->data['decision'] ?? null);
        $this->assertSame($order->order_code, $notification->data['order_code'] ?? null);
    }

    public function test_admin_cannot_mark_bank_transfer_order_as_paid_without_proof_from_status_form(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'MCB Without Proof',
            'slug' => 'mcb-without-proof',
            'price' => 110000,
            'stock' => 5,
        ]);

        [$order, $payment] = $this->createPendingOrder($customer, $product, 1, now()->subMinutes(20));
        $payment->update([
            'method' => 'bank_transfer',
            'proof_url' => null,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), [
                'status' => 'pending',
                'payment_status' => 'paid',
                'tracking_number' => '',
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error');

        $order->refresh();
        $payment->refresh();

        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->paid_at);
        $this->assertSame('pending', $payment->status);
    }

    public function test_admin_can_reject_payment_proof_and_customer_can_reupload(): void
    {
        $this->fakeSensitiveDisks();

        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Kabel Reject Flow',
            'slug' => 'kabel-reject-flow',
            'price' => 88000,
            'stock' => 6,
        ]);

        [$order, $payment] = $this->createPendingOrder($customer, $product, 1, now()->subMinutes(25));

        $oldProofPath = 'payments/' . $order->order_code . '/proof-old.jpg';
        Storage::disk('public')->put($oldProofPath, 'proof lama');

        $payment->update([
            'proof_url' => $oldProofPath,
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $rejectResponse = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.payments.reject', [$order, $payment]), [
                'admin_notes' => 'Nominal transfer tidak sesuai dengan total invoice.',
            ]);

        $rejectResponse->assertRedirect(route('admin.orders.show', $order));
        $rejectResponse->assertSessionHas('success');

        $order->refresh();
        $payment->refresh();

        $this->assertSame('failed', $order->payment_status);
        $this->assertNull($order->paid_at);
        $this->assertSame('failed', $payment->status);
        $this->assertStringContainsString('Bukti pembayaran ditolak admin', (string) $payment->notes);

        $notification = $customer->fresh()->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame(PaymentProofStatusUpdatedNotification::class, $notification->type);
        $this->assertSame('rejected', $notification->data['decision'] ?? null);
        $this->assertStringContainsString('Nominal transfer tidak sesuai', (string) ($notification->data['admin_notes'] ?? ''));

        $uploadResponse = $this->actingAs($customer)
            ->from(route('home.tracking'))
            ->post(route('home.tracking.proof', $order->order_code), [
                'replace_proof' => 1,
                'payment_proof' => UploadedFile::fake()->image('proof-new.jpg'),
            ]);

        $uploadResponse->assertRedirect(route('home.tracking'));
        $uploadResponse->assertSessionHas('success');

        $order->refresh();
        $payment->refresh();

        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->proof_url);
        $this->assertNotSame($oldProofPath, $payment->proof_url);
        $this->assertFalse(Storage::disk('public')->exists($oldProofPath));
        $this->assertTrue(Storage::disk('local')->exists((string) $payment->proof_url));
        $this->assertFalse(Storage::disk('public')->exists((string) $payment->proof_url));
    }

    public function test_admin_must_provide_reason_when_rejecting_payment_proof(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Pompa Reject Validation',
            'slug' => 'pompa-reject-validation',
            'price' => 210000,
            'stock' => 4,
        ]);

        [$order, $payment] = $this->createPendingOrder($customer, $product, 1, now()->subMinutes(35));

        $payment->update([
            'proof_url' => 'payments/' . $order->order_code . '/proof-validation.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.payments.reject', [$order, $payment]), [
                'admin_notes' => '',
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHasErrors('admin_notes');

        $order->refresh();
        $payment->refresh();

        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $payment->status);
    }

    public function test_auto_cancel_command_cancels_only_expired_pending_orders_and_restores_stock(): void
    {
        $user = User::factory()->create();

        $expiredProduct = $this->createProduct([
            'name' => 'Lampu Auto Cancel',
            'slug' => 'lampu-auto-cancel',
            'price' => 50000,
            // Simulasi stok tersisa setelah checkout qty 2 dari stok awal 10.
            'stock' => 8,
        ]);

        [$expiredOrder, $expiredPayment] = $this->createPendingOrder(
            $user,
            $expiredProduct,
            2,
            now()->subHours(2),
        );

        $freshProduct = $this->createProduct([
            'name' => 'Saklar Fresh',
            'slug' => 'saklar-fresh',
            'price' => 25000,
            // Simulasi stok tersisa setelah checkout qty 1 dari stok awal 6.
            'stock' => 5,
        ]);

        [$freshOrder, $freshPayment] = $this->createPendingOrder(
            $user,
            $freshProduct,
            1,
            now()->subMinutes(20),
        );

        $this->artisan('orders:cancel-unpaid')->assertSuccessful();

        $expiredOrder->refresh();
        $expiredPayment->refresh();
        $expiredProduct->refresh();

        $this->assertSame('cancelled', $expiredOrder->status);
        $this->assertSame('failed', $expiredOrder->payment_status);
        $this->assertSame('void', $expiredOrder->warranty_status);
        $this->assertStringContainsString('Auto-cancel sistem', (string) $expiredOrder->notes);
        $this->assertSame('failed', $expiredPayment->status);
        $this->assertNull($expiredPayment->paid_at);
        $this->assertSame(10, (int) $expiredProduct->stock);

        $freshOrder->refresh();
        $freshPayment->refresh();
        $freshProduct->refresh();

        $this->assertSame('pending', $freshOrder->status);
        $this->assertSame('pending', $freshOrder->payment_status);
        $this->assertSame('pending', $freshPayment->status);
        $this->assertSame(5, (int) $freshProduct->stock);
    }

    public function test_user_cannot_submit_second_active_warranty_claim_for_same_order_item(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Stop Kontak Klaim',
            'slug' => 'stop-kontak-klaim',
            'price' => 30000,
            'stock' => 4,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(30));

        $firstResponse = $this->actingAs($user)
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Produk mati total setelah dipakai beberapa hari.',
                'damage_proof' => UploadedFile::fake()->image('rusak-1.jpg'),
            ]);

        $firstResponse->assertRedirect(route('home.cart'));
        $firstResponse->assertSessionHas('success');
        $this->assertDatabaseCount('warranty_claims', 1);

        $secondResponse = $this->actingAs($user)
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Klaim kedua, produk masih tidak menyala sama sekali.',
                'damage_proof' => UploadedFile::fake()->image('rusak-2.jpg'),
            ]);

        $secondResponse->assertRedirect(route('home.cart'));
        $secondResponse->assertSessionHas('error');
        $this->assertDatabaseCount('warranty_claims', 1);
    }

    public function test_user_can_submit_warranty_claim_again_after_previous_claims_are_closed(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'MCB Limit Klaim',
            'slug' => 'mcb-limit-klaim',
            'price' => 99000,
            'stock' => 5,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(30));

        WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $user->id,
            'reason' => 'Klaim pertama selesai diproses.',
            'status' => 'resolved',
            'requested_at' => now()->subDays(2),
            'resolved_at' => now()->subDay(),
        ]);

        WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $user->id,
            'reason' => 'Klaim kedua selesai diproses.',
            'status' => 'rejected',
            'requested_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('home.cart'))
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Klaim diajukan kembali setelah dua klaim sebelumnya ditutup.',
                'damage_proof' => UploadedFile::fake()->image('rusak-ulang.jpg'),
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('warranty_claims', 3);
    }

    public function test_user_cannot_submit_warranty_claim_for_non_electronic_item(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Terminal Non Elektronik',
            'slug' => 'terminal-non-elektronik',
            'price' => 27000,
            'stock' => 10,
            'is_electronic' => false,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(40));

        $response = $this->actingAs($user)
            ->from(route('home.cart'))
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Mengajukan klaim untuk produk non-elektronik.',
                'damage_proof' => UploadedFile::fake()->image('non-electronic.jpg'),
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('warranty_claims', 0);
    }

    public function test_claim_submission_stores_damage_proof_file(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Kipas Angin Test Bukti',
            'slug' => 'kipas-angin-test-bukti',
            'price' => 180000,
            'stock' => 3,
            'is_electronic' => true,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subMinutes(20));

        $response = $this->actingAs($user)
            ->from(route('home.cart'))
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Kipas berputar sangat lambat meski setelan sudah maksimal.',
                'damage_proof' => UploadedFile::fake()->image('bukti-kerusakan.jpg'),
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('success');

        $claim = WarrantyClaim::query()->latest('id')->first();
        $this->assertNotNull($claim);
        $this->assertNotNull($claim?->damage_proof_url);
        $this->assertStringNotContainsString('bukti-kerusakan', (string) $claim?->damage_proof_url);
        $this->assertTrue(Storage::disk('local')->exists((string) $claim?->damage_proof_url));
        $this->assertFalse(Storage::disk('public')->exists((string) $claim?->damage_proof_url));
    }

    public function test_warranty_claim_proof_endpoint_allows_owner_and_admin_only(): void
    {
        $this->fakeSensitiveDisks();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = $this->createAdminUser();
        $product = $this->createProduct([
            'name' => 'Pompa Proof Endpoint',
            'slug' => 'pompa-proof-endpoint',
            'price' => 260000,
            'stock' => 4,
            'is_electronic' => true,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($owner, $product, 1, now()->subMinutes(20));
        $claimCode = 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        $proofPath = UploadedFile::fake()->image('damage-private.jpg')->store('warranty-claims/' . $claimCode, 'local');

        $claim = WarrantyClaim::create([
            'claim_code' => $claimCode,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $owner->id,
            'reason' => 'Pompa tidak menyala setelah digunakan.',
            'status' => 'submitted',
            'requested_at' => now(),
            'damage_proof_url' => $proofPath,
            'damage_proof_mime' => 'image/jpeg',
        ]);

        $route = route('home.warranty-claims.proof.view', $claim);

        $ownerResponse = $this->actingAs($owner)->get($route);
        $ownerResponse->assertOk();
        $this->assertSame('image/jpeg', $ownerResponse->headers->get('content-type'));

        $this->actingAs($admin)->get($route)->assertOk();
        $this->actingAs($otherUser)->get($route)->assertForbidden();
    }

    public function test_legacy_public_warranty_claim_proof_is_still_served_through_protected_endpoint(): void
    {
        $this->fakeSensitiveDisks();

        $owner = User::factory()->create();
        $product = $this->createProduct([
            'name' => 'Pompa Legacy Proof',
            'slug' => 'pompa-legacy-proof',
            'price' => 275000,
            'stock' => 4,
            'is_electronic' => true,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($owner, $product, 1, now()->subMinutes(20));
        $claimCode = 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        $legacyProofPath = UploadedFile::fake()->image('damage-legacy.jpg')->store('warranty-claims/' . $claimCode, 'public');

        $claim = WarrantyClaim::create([
            'claim_code' => $claimCode,
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $owner->id,
            'reason' => 'Pompa bocor setelah digunakan.',
            'status' => 'submitted',
            'requested_at' => now(),
            'damage_proof_url' => $legacyProofPath,
            'damage_proof_mime' => 'image/jpeg',
        ]);

        $response = $this->actingAs($owner)->get(route('home.warranty-claims.proof.view', $claim));

        $response->assertOk();
        $this->assertSame('image/jpeg', $response->headers->get('content-type'));
    }

    public function test_admin_can_update_order_item_warranty_date_within_product_warranty_window(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Stop Kontak Admin Garansi',
            'slug' => 'stop-kontak-admin-garansi',
            'price' => 77000,
            'stock' => 6,
            'warranty_days' => 30,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($customer, $product, 1, now()->subHours(2));

        $warrantyStart = ($order->completed_at ?? $order->placed_at ?? $order->created_at)->copy()->startOfDay();
        $newExpiryDate = $warrantyStart->copy()->addDays(15)->toDateString();

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.items.update-warranty', [$order, $orderItem]), [
                'warranty_expires_at' => $newExpiryDate,
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('success');

        $orderItem->refresh();
        $this->assertSame(15, (int) $orderItem->warranty_days);
        $this->assertSame($newExpiryDate, $orderItem->warranty_expires_at?->toDateString());
    }

    public function test_admin_cannot_set_order_item_warranty_date_more_than_product_limit(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Kabel Admin Garansi',
            'slug' => 'kabel-admin-garansi',
            'price' => 38000,
            'stock' => 7,
            'warranty_days' => 30,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($customer, $product, 1, now()->subHours(1));

        $warrantyStart = ($order->completed_at ?? $order->placed_at ?? $order->created_at)->copy()->startOfDay();
        $invalidExpiryDate = $warrantyStart->copy()->addDays(31)->toDateString();

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.items.update-warranty', [$order, $orderItem]), [
                'warranty_expires_at' => $invalidExpiryDate,
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error');

        $orderItem->refresh();
        $this->assertSame(30, (int) $orderItem->warranty_days);
    }

    public function test_admin_cannot_update_warranty_for_non_electronic_item(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Pipa PVC',
            'slug' => 'pipa-pvc',
            'price' => 23000,
            'stock' => 12,
            'is_electronic' => false,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($customer, $product, 1, now()->subHours(2));

        $warrantyStart = ($order->completed_at ?? $order->placed_at ?? $order->created_at)->copy()->startOfDay();
        $requestedExpiryDate = $warrantyStart->copy()->addDays(3)->toDateString();

        $response = $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.items.update-warranty', [$order, $orderItem]), [
                'warranty_expires_at' => $requestedExpiryDate,
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error');

        $orderItem->refresh();
        $this->assertSame(0, (int) $orderItem->warranty_days);
        $this->assertNull($orderItem->warranty_expires_at);
    }

    public function test_user_can_view_warranty_claim_history_with_admin_note_and_update_time(): void
    {
        $this->fakeSensitiveDisks();

        $user = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Blender Rumah',
            'slug' => 'blender-rumah',
            'price' => 240000,
            'stock' => 6,
            'is_electronic' => true,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($user, $product, 1, now()->subHours(4));

        $this->actingAs($user)
            ->post(route('home.warranty-claims.store', [$order, $orderItem]), [
                'reason' => 'Blender mati total saat pertama kali dipakai.',
                'damage_proof' => UploadedFile::fake()->image('blender-rusak.jpg'),
            ]);

        $claim = WarrantyClaim::query()->latest('id')->firstOrFail();
        $claim->update([
            'status' => 'reviewing',
            'admin_notes' => 'Tim admin sedang verifikasi kerusakan perangkat.',
        ]);

        $claim->activities()->create([
            'actor_id' => null,
            'actor_name' => 'Admin QA',
            'action' => 'status_reviewing',
            'from_status' => 'submitted',
            'to_status' => 'reviewing',
            'note' => 'Masuk antrian review teknisi.',
        ]);

        $response = $this->actingAs($user)->get(route('home.warranty-claims.index'));

        $response->assertOk();
        $response->assertSee($claim->claim_code);
        $response->assertSee('Tim admin sedang verifikasi kerusakan perangkat.');
        $response->assertSee($order->order_code);
    }

    public function test_admin_must_provide_reason_when_rejecting_claim(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $product = $this->createProduct([
            'name' => 'Mesin Pompa Air',
            'slug' => 'mesin-pompa-air',
            'price' => 350000,
            'stock' => 4,
            'is_electronic' => true,
        ]);

        [$order,, $orderItem] = $this->createPendingOrder($customer, $product, 1, now()->subHours(3));

        $claim = WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $customer->id,
            'reason' => 'Pompa tidak menyedot air sama sekali.',
            'status' => 'submitted',
            'requested_at' => now()->subHours(2),
            'damage_proof_url' => 'warranty-claims/mock/proof.jpg',
            'damage_proof_mime' => 'image/jpeg',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.warranty-claims.index'))
            ->patch(route('admin.warranty-claims.update-status', $claim), [
                'status' => 'rejected',
                'admin_notes' => '',
            ]);

        $response->assertRedirect(route('admin.warranty-claims.index'));
        $response->assertSessionHasErrors('admin_notes');

        $claim->refresh();
        $this->assertSame('submitted', $claim->status);
    }

    public function test_admin_can_filter_warranty_claim_by_electronic_and_age_bucket(): void
    {
        $admin = $this->createAdminUser();
        $customer = User::factory()->create();

        $electronicProduct = $this->createProduct([
            'name' => 'Kipas Filter',
            'slug' => 'kipas-filter',
            'price' => 130000,
            'stock' => 8,
            'is_electronic' => true,
        ]);

        $nonElectronicProduct = $this->createProduct([
            'name' => 'Pipa Filter',
            'slug' => 'pipa-filter',
            'price' => 12000,
            'stock' => 20,
            'is_electronic' => false,
        ]);

        [$oldOrder,, $oldItem] = $this->createPendingOrder($customer, $electronicProduct, 1, now()->subDays(4));
        [$newOrder,, $newItem] = $this->createPendingOrder($customer, $nonElectronicProduct, 1, now()->subHours(10));

        $oldClaim = WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $oldOrder->id,
            'order_item_id' => $oldItem->id,
            'user_id' => $customer->id,
            'reason' => 'Kipas berisik dan tidak stabil.',
            'status' => 'submitted',
            'requested_at' => now()->subDays(4),
        ]);

        $newClaim = WarrantyClaim::create([
            'claim_code' => 'WRN-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'order_id' => $newOrder->id,
            'order_item_id' => $newItem->id,
            'user_id' => $customer->id,
            'reason' => 'Pipa retak.',
            'status' => 'submitted',
            'requested_at' => now()->subHours(10),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.warranty-claims.index', [
                'electronic' => 'electronic',
                'age_bucket' => '3_7d',
            ]));

        $response->assertOk();
        $response->assertSee($oldClaim->claim_code);
        $response->assertDontSee($newClaim->claim_code);
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create([
            'name' => $overrides['category_name'] ?? 'Kategori ' . Str::upper(Str::random(4)),
            'slug' => $overrides['category_slug'] ?? 'kategori-' . Str::lower(Str::random(6)),
        ]);

        $isElectronicProduct = (bool) ($overrides['is_electronic'] ?? true);
        $warrantyDays = $isElectronicProduct
            ? max(1, min(365, (int) ($overrides['warranty_days'] ?? 7)))
            : 0;

        return Product::create([
            'category_id' => $category->id,
            'name' => $overrides['name'] ?? 'Produk ' . Str::upper(Str::random(4)),
            'slug' => $overrides['slug'] ?? 'produk-' . Str::lower(Str::random(6)),
            'description' => $overrides['description'] ?? 'Produk untuk pengujian fitur transaksi.',
            'price' => $overrides['price'] ?? 10000,
            'stock' => $overrides['stock'] ?? 10,
            'unit' => $overrides['unit'] ?? 'pcs',
            'is_active' => $overrides['is_active'] ?? true,
            'is_electronic' => $isElectronicProduct,
            'warranty_days' => $warrantyDays,
        ]);
    }

    /**
     * @return array{0: Order, 1: Payment, 2: OrderItem}
     */
    private function createPendingOrder(User $user, Product $product, int $quantity, \DateTimeInterface $placedAt): array
    {
        $subtotal = (int) $product->price * $quantity;
        $warrantyDays = (int) $product->warranty_days_for_claim;

        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'address_id' => null,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081234567890',
            'notes' => 'Order untuk testing lifecycle.',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => $subtotal,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'placed_at' => $placedAt,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => $product->unit,
            'price' => (int) $product->price,
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'warranty_days' => $warrantyDays,
            'warranty_expires_at' => $warrantyDays > 0 ? now()->addDays($warrantyDays) : null,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_code' => 'PAY-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'method' => 'bank_transfer',
            'amount' => $subtotal,
            'status' => 'pending',
            'notes' => 'Payment transfer untuk testing.',
        ]);

        return [$order, $payment, $orderItem];
    }

    private function createAdminUser(): User
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function fakeSensitiveDisks(): void
    {
        Storage::fake('local');
        Storage::fake('public');
    }
}
