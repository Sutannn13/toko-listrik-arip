<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssertsStorefrontSnapshots;
use Tests\TestCase;

class StorefrontVisualSnapshotTest extends TestCase
{
    use AssertsStorefrontSnapshots;
    use RefreshDatabase;

    public function test_root_url_visual_snapshot_matches_catalog_baseline(): void
    {
        $this->seedStorefrontProducts();

        $response = $this->get('/');

        $response->assertOk();
        $this->assertMatchesStorefrontSnapshot($response->getContent(), 'catalog-main');
    }

    public function test_catalog_page_visual_snapshot_matches_baseline(): void
    {
        $this->seedStorefrontProducts();

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertMatchesStorefrontSnapshot($response->getContent(), 'catalog-main');
    }

    public function test_product_detail_page_visual_snapshot_matches_baseline(): void
    {
        $product = $this->seedStorefrontProducts();

        $response = $this->get(route('home.products.show', $product->slug));

        $response->assertOk();
        $this->assertMatchesStorefrontSnapshot($response->getContent(), 'product-detail-main');
    }

    private function seedStorefrontProducts(): Product
    {
        $category = Category::create([
            'name' => 'Kipas Angin',
            'slug' => 'kipas-angin',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Kipas Meja Turbo',
            'slug' => 'kipas-meja-turbo',
            'description' => 'Kipas meja hemat daya untuk ruangan kecil.',
            'price' => 185000,
            'stock' => 15,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Kipas Dinding Pro',
            'slug' => 'kipas-dinding-pro',
            'description' => 'Kipas dinding aliran kuat untuk area kerja.',
            'price' => 275000,
            'stock' => 9,
            'unit' => 'pcs',
            'is_active' => true,
            'is_electronic' => true,
        ]);
    }
}
