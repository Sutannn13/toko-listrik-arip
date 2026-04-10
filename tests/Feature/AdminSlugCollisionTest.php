<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSlugCollisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_categories_with_colliding_slug_base(): void
    {
        $admin = $this->createAdmin();

        $firstResponse = $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Kabel-Listrik',
            ]);

        $firstResponse->assertRedirect(route('admin.categories.index'));
        $firstResponse->assertSessionHas('success');

        $secondResponse = $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Kabel Listrik',
            ]);

        $secondResponse->assertRedirect(route('admin.categories.index'));
        $secondResponse->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Kabel-Listrik',
            'slug' => 'kabel-listrik',
        ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Kabel Listrik',
            'slug' => 'kabel-listrik-2',
        ]);
    }

    public function test_admin_can_create_products_with_colliding_slug_base(): void
    {
        $admin = $this->createAdmin();

        $category = Category::create([
            'name' => 'Lampu',
            'slug' => 'lampu',
        ]);

        $firstResponse = $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Lampu LED-12W',
                'category_id' => $category->id,
                'price' => 25000,
                'stock' => 15,
                'unit' => 'pcs',
                'description' => 'Produk lampu pertama.',
                'is_electronic' => '1',
            ]);

        $firstResponse->assertRedirect(route('admin.products.index'));
        $firstResponse->assertSessionHas('success');

        $secondResponse = $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'name' => 'Lampu LED 12W',
                'category_id' => $category->id,
                'price' => 28000,
                'stock' => 12,
                'unit' => 'pcs',
                'description' => 'Produk lampu kedua.',
                'is_electronic' => '1',
            ]);

        $secondResponse->assertRedirect(route('admin.products.index'));
        $secondResponse->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Lampu LED-12W',
            'slug' => 'lampu-led-12w',
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Lampu LED 12W',
            'slug' => 'lampu-led-12w-2',
        ]);
    }

    private function createAdmin(): User
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
