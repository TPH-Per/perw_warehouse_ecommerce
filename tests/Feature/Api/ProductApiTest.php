<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
        ]);

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test description',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-001',
            'size' => 'M',
            'color' => 'Red',
            'price' => 100000,
            'stock_quantity' => 10,
        ]);
    }

    public function test_can_get_products_list(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'category', 'variants'],
                ],
                'current_page',
                'per_page',
            ]);
    }

    public function test_can_get_product_by_id(): void
    {
        $product = Product::first();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'name', 'slug', 'description', 'category', 'variants', 'images',
            ])
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
            ]);
    }

    public function test_can_get_product_by_slug(): void
    {
        $product = Product::first();

        $response = $this->getJson("/api/products/slug/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'name', 'slug', 'description', 'category', 'variants',
            ])
            ->assertJson([
                'slug' => $product->slug,
            ]);
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::first();

        $response = $this->getJson("/api/products?category_id={$category->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $product) {
            $this->assertEquals($category->id, $product['category']['id']);
        }
    }

    public function test_can_search_products_by_name(): void
    {
        $response = $this->getJson('/api/products?search=Test');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('Test', $data[0]['name']);
    }

    public function test_can_get_featured_products(): void
    {
        $response = $this->getJson('/api/products/featured');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'slug', 'variants'],
            ]);
    }

    public function test_can_search_products(): void
    {
        $response = $this->getJson('/api/products/search?q=Test');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'slug'],
            ]);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/products/99999');

        $response->assertStatus(404);
    }
}
