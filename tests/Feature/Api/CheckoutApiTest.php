<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProductVariant $variant;
    protected PaymentMethod $paymentMethod;
    protected ShippingMethod $shippingMethod;

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
            'supplier_id' => $supplier->id,
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

        // Create payment and shipping methods
        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Cash on Delivery',
            'code' => 'COD',
        ]);

        $this->shippingMethod = ShippingMethod::create([
            'name' => 'Standard Shipping',
            'code' => 'STANDARD',
            'cost' => 50000,
        ]);
    }

    public function test_can_view_checkout_information(): void
    {
        // Create cart with items
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => $this->variant->price,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checkout');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cart' => [
                    'id',
                    'items' => [
                        '*' => ['id', 'product_variant_id', 'quantity', 'price', 'subtotal', 'variant'],
                    ],
                    'total_items',
                ],
                'payment_methods',
                'shipping_methods',
                'pricing' => [
                    'sub_total',
                    'shipping_fee',
                    'discount_amount',
                    'total_amount',
                ],
            ]);
    }

    public function test_cannot_view_checkout_with_empty_cart(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checkout');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cart is empty',
            ]);
    }

    public function test_unauthenticated_user_cannot_access_checkout(): void
    {
        $response = $this->getJson('/api/checkout');

        $response->assertStatus(401);
    }

    public function test_checkout_shows_correct_pricing_calculation(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 3,
            'price' => 100000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checkout');

        $response->assertStatus(200)
            ->assertJson([
                'pricing' => [
                    'sub_total' => 300000, // 3 * 100000
                    'shipping_fee' => 50000,
                    'discount_amount' => 0,
                    'total_amount' => 350000, // 300000 + 50000
                ],
            ]);
    }

    public function test_checkout_shows_all_payment_methods(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'price' => $this->variant->price,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checkout');

        $response->assertStatus(200);

        $paymentMethods = $response->json('payment_methods');
        $this->assertCount(1, $paymentMethods);
        $this->assertEquals('Cash on Delivery', $paymentMethods[0]['name']);
    }
}
