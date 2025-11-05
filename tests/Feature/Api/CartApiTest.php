<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create role and user
        $role = Role::create(['name' => 'Customer', 'description' => 'Customer role']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);

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
'status' => 'published',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU-001',
            'size' => 'M',
            'color' => 'Red',
            'price' => 100000,
            'stock_quantity' => 10,
        ]);

        // Create warehouse and inventory
        $warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'location' => 'Test Location',
        ]);

        Inventory::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id' => $warehouse->id,
            'quantity_on_hand' => 100,
            'quantity_reserved' => 0,
        ]);
    }

    public function test_can_get_empty_cart(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'user_id', 'cart_details', 'total_items', 'total_amount',
            ])
            ->assertJson([
                'total_items' => 0,
                'total_amount' => 0,
            ]);
    }

    public function test_can_add_item_to_cart(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cart', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'cart_details', 'total_items', 'total_amount',
            ]);

        $this->assertDatabaseHas('cart_details', [
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cart', [
                'product_variant_id' => $this->variant->id,
                'quantity' => 200, // More than available stock
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Insufficient stock available',
            ]);
    }

    public function test_can_update_cart_item_quantity(): void
    {
        // First add item to cart
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartDetail = CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => $this->variant->price,
        ]);

        // Update quantity
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/cart/{$cartDetail->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cart_details', [
            'id' => $cartDetail->id,
            'quantity' => 5,
        ]);
    }

    public function test_can_remove_cart_item_by_setting_quantity_to_zero(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartDetail = CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => $this->variant->price,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/cart/{$cartDetail->id}", [
                'quantity' => 0,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('cart_details', [
            'id' => $cartDetail->id,
        ]);
    }

    public function test_can_delete_cart_item(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cartDetail = CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => $this->variant->price,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/cart/{$cartDetail->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('cart_details', [
            'id' => $cartDetail->id,
        ]);
    }

    public function test_can_clear_entire_cart(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => $this->variant->price,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/cart/clear');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Cart cleared successfully',
            ]);

        $this->assertDatabaseMissing('cart_details', [
            'cart_id' => $cart->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_cart(): void
    {
        $response = $this->getJson('/api/cart');

        $response->assertStatus(401);
    }
}
