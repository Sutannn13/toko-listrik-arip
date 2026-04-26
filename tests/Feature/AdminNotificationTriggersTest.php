<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\AdminPaymentProofUploadedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_sends_admin_notification_when_toggle_is_enabled(): void
    {
        [$admin, $customer] = $this->createAdminAndCustomer();

        $category = Category::create([
            'name' => 'Notif Checkout',
            'slug' => 'notif-checkout',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'MCB 4A',
            'slug' => 'mcb-4a',
            'description' => 'Produk untuk test notifikasi checkout.',
            'price' => 15000,
            'stock' => 20,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($customer)
            ->withSession([
                'simple_cart' => [
                    $product->id => [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => (int) $product->price,
                        'unit' => $product->unit,
                        'qty' => 2,
                    ],
                ],
            ])
            ->post(route('home.cart.checkout'), [
                'payment_method' => 'cod',
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => '081200000001',
                'address_label' => 'Rumah',
                'recipient_name' => $customer->name,
                'address_phone' => '081200000001',
                'address_line' => 'Jl. Notif Checkout',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40123',
            ]);

        $response->assertRedirect(route('home.cart'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => AdminNewOrderNotification::class,
        ]);
    }

    public function test_upload_payment_proof_sends_admin_notification_when_toggle_is_enabled(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        [$admin, $customer] = $this->createAdminAndCustomer();

        Setting::where('key', 'notif_order_paid')->update(['value' => '1']);

        $order = Order::create([
            'order_code' => 'ORD-ARIP-20260412-NOTIF1',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081200000002',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 50000,
            'shipping_cost' => 5000,
            'discount_amount' => 0,
            'total_amount' => 55000,
            'placed_at' => now(),
        ]);

        $payment = $order->payments()->create([
            'payment_code' => 'PAY-ARIP-20260412-NOTIF1',
            'method' => 'bank_transfer',
            'amount' => 55000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer)
            ->post(route('home.tracking.proof', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('proof.jpg'),
            ]);

        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertNotNull($payment->proof_url);
        $this->assertTrue(Storage::disk('local')->exists((string) $payment->proof_url));
        $this->assertFalse(Storage::disk('public')->exists((string) $payment->proof_url));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => AdminPaymentProofUploadedNotification::class,
        ]);
    }

    public function test_admin_clicking_notification_preview_marks_it_as_read(): void
    {
        [$admin, $customer] = $this->createAdminAndCustomer();

        $order = Order::create([
            'order_code' => 'ORD-ARIP-20260413-OPEN01',
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '081200000099',
            'status' => 'pending',
            'payment_status' => 'pending',
            'warranty_status' => 'active',
            'subtotal' => 100000,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'placed_at' => now(),
        ]);

        $admin->notify(new AdminNewOrderNotification($order));

        $notification = $admin->unreadNotifications()->first();
        $this->assertNotNull($notification);

        $response = $this->actingAs($admin)
            ->get(route('admin.notifications.open', ['notification' => $notification->id]));

        $response->assertRedirect(route('admin.orders.show', $order, absolute: false));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function createAdminAndCustomer(): array
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');

        $admin = User::factory()->create([
            'email' => 'admin-notif@example.com',
        ]);
        $admin->assignRole('admin');

        $customer = User::factory()->create([
            'email' => 'customer-notif@example.com',
        ]);
        $customer->assignRole('user');

        return [$admin, $customer];
    }
}
