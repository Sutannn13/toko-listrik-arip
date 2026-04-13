<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiCartEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_cart_list_returns_empty_cart_for_new_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.cart.index'));

        $response->assertOk();
        $response->assertJsonPath('message', 'Data cart berhasil diambil.');
        $response->assertJsonCount(0, 'data.items');
        $response->assertJsonPath('data.totals.total_quantity', 0);
        $response->assertJsonPath('data.totals.subtotal', 0);
    }

    public function test_api_cart_add_item_creates_cart_and_returns_totals(): void
    {
        [$user, $product] = $this->createUserAndProduct();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Produk berhasil ditambahkan ke cart.');
        $response->assertJsonPath('data.totals.total_quantity', 2);
        $response->assertJsonPath('data.totals.subtotal', 30000);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_api_cart_update_item_changes_quantity(): void
    {
        [$user, $product] = $this->createUserAndProduct();
        Sanctum::actingAs($user);

        $this->postJson(route('api.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertCreated();

        $response = $this->patchJson(route('api.cart.items.update', ['productId' => $product->id]), [
            'quantity' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Jumlah item cart berhasil diperbarui.');
        $response->assertJsonPath('data.totals.total_quantity', 5);
        $response->assertJsonPath('data.totals.subtotal', 75000);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_api_cart_remove_item_deletes_item_from_cart(): void
    {
        [$user, $product] = $this->createUserAndProduct();
        Sanctum::actingAs($user);

        $this->postJson(route('api.cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertCreated();

        $response = $this->deleteJson(route('api.cart.items.destroy', ['productId' => $product->id]));

        $response->assertOk();
        $response->assertJsonPath('message', 'Produk berhasil dihapus dari cart.');
        $response->assertJsonCount(0, 'data.items');

        $this->assertDatabaseMissing('cart_items', [
            'product_id' => $product->id,
        ]);
    }

    private function createUserAndProduct(): array
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => 'API Cart Category',
            'slug' => 'api-cart-category',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kabel NYA 2.5mm',
            'slug' => 'kabel-nya-2-5mm',
            'description' => 'Produk untuk test endpoint API cart.',
            'price' => 15000,
            'stock' => 20,
            'unit' => 'roll',
            'is_active' => true,
            'is_electronic' => false,
        ]);

        return [$user, $product];
    }
}
