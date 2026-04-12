<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_submission_rejects_image_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->createCompletedPurchase($user, $product);

        $response = $this
            ->actingAs($user)
            ->post(route('home.products.review', $product->slug), [
                'rating' => 5,
                'comment' => 'Produk bagus dan sesuai kebutuhan.',
                'image' => UploadedFile::fake()->image('review.jpg'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('image');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_submitting_review_clears_previous_review_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $product = $this->createProduct();

        $this->createCompletedPurchase($user, $product);

        $oldImagePath = UploadedFile::fake()->image('old-review.jpg')->store('reviews', 'public');

        Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 3,
            'comment' => 'Ulasan lama.',
            'image' => $oldImagePath,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('home.products.review', $product->slug), [
                'rating' => 5,
                'comment' => 'Sekarang saya puas, kualitas bagus.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Sekarang saya puas, kualitas bagus.',
            'image' => null,
        ]);

        $this->assertFalse(Storage::disk('public')->exists($oldImagePath));
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Kategori Review ' . Str::upper(Str::random(4)),
            'slug' => 'kategori-review-' . Str::lower(Str::random(6)),
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Produk Review ' . Str::upper(Str::random(3)),
            'slug' => 'produk-review-' . Str::lower(Str::random(6)),
            'description' => 'Produk untuk pengujian ulasan.',
            'price' => 100000,
            'stock' => 10,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
    }

    private function createCompletedPurchase(User $user, Product $product): void
    {
        $order = Order::create([
            'order_code' => 'ORD-ARIP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '081234567890',
            'notes' => 'Order untuk test review',
            'status' => 'completed',
            'payment_status' => 'paid',
            'warranty_status' => 'active',
            'subtotal' => (int) $product->price,
            'shipping_cost' => 0,
            'discount_amount' => 0,
            'total_amount' => (int) $product->price,
            'placed_at' => now(),
            'paid_at' => now(),
            'completed_at' => now(),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'unit' => $product->unit,
            'price' => (int) $product->price,
            'quantity' => 1,
            'subtotal' => (int) $product->price,
            'warranty_days' => $product->is_electronic ? 7 : 0,
            'warranty_expires_at' => $product->is_electronic ? now()->addDays(7) : null,
        ]);
    }
}
