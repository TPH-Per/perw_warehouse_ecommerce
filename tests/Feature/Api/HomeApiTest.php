<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $category1 = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $category2 = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
        ]);

        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
        ]);

        // Create products
        $product1 = Product::create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'description' => 'High-end laptop',
'status' => 'published',
        ]);

        ProductVariant::create([
            'product_id' => $product1->id,
            'sku' => 'LAPTOP-001',
            'size' => 'N/A',
            'color' => 'Silver',
            'price' => 1500000,
            'stock_quantity' => 5,
        ]);

        $product2 = Product::create([
            'name' => 'T-Shirt',
            'slug' => 't-shirt',
            'description' => 'Cotton t-shirt',
            'category_id' => $category2->id,
            'supplier_id' => $supplier->id,
            'status' => 'active',
        ]);

        ProductVariant::create([
            'product_id' => $product2->id,
            'sku' => 'TSHIRT-001',
            'size' => 'M',
            'color' => 'Blue',
            'price' => 50000,
            'stock_quantity' => 20,
        ]);
    }

    public function test_can_get_home_products_list(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'products' => [
                    'data' => [
                        '*' => ['id', 'name', 'slug', 'category', 'variants'],
                    ],
                    'current_page',
                    'per_page',
                ],
                'categories',
                'filters',
            ]);
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::where('slug', 'electronics')->first();

        $response = $this->getJson("/api/home?category_id={$category->id}");

        $response->assertStatus(200);

        $products = $response->json('products.data');
        foreach ($products as $product) {
            $this->assertEquals($category->id, $product['category']['id']);
        }
    }

    public function test_can_search_products_by_name(): void
    {
        $response = $this->getJson('/api/home?q=Laptop');

        $response->assertStatus(200);

        $products = $response->json('products.data');
        $this->assertNotEmpty($products);
        $this->assertStringContainsString('Laptop', $products[0]['name']);
    }

    public function test_can_sort_products_by_name(): void
    {
        $response = $this->getJson('/api/home?sort=name');

        $response->assertStatus(200);

        $products = $response->json('products.data');
        $this->assertNotEmpty($products);
    }

    public function test_can_sort_products_by_price_low_to_high(): void
    {
        $response = $this->getJson('/api/home?sort=price_low');

        $response->assertStatus(200);

        $products = $response->json('products.data');
        $this->assertNotEmpty($products);
        // First product should be T-Shirt (cheaper)
        $this->assertEquals('T-Shirt', $products[0]['name']);
    }

    public function test_can_sort_products_by_price_high_to_low(): void
    {
        $response = $this->getJson('/api/home?sort=price_high');

        $response->assertStatus(200);

        $products = $response->json('products.data');
        $this->assertNotEmpty($products);
        // First product should be Laptop (more expensive)
        $this->assertEquals('Laptop', $products[0]['name']);
    }

    public function test_default_sort_is_newest(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200)
            ->assertJson([
                'filters' => [
                    'sort' => 'newest',
                ],
            ]);
    }

    public function test_can_get_featured_products(): void
    {
        $response = $this->getJson('/api/home/featured');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'products' => [
                    '*' => ['id', 'name', 'slug', 'variants'],
                ],
            ]);

        $products = $response->json('products');
        $this->assertLessThanOrEqual(8, count($products));
    }

    public function test_can_customize_featured_products_limit(): void
    {
        $response = $this->getJson('/api/home/featured?limit=3');

        $response->assertStatus(200);

        $products = $response->json('products');
        $this->assertLessThanOrEqual(3, count($products));
    }

    public function test_returns_categories_list(): void
    {
        $response = $this->getJson('/api/home');

        $response->assertStatus(200);

        $categories = $response->json('categories');
        $this->assertCount(2, $categories);
        $this->assertEquals('Clothing', $categories[0]['name']);
        $this->assertEquals('Electronics', $categories[1]['name']);
    }
}
