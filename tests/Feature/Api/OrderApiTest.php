<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ProductVariant $variant;
    protected PaymentMethod $paymentMethod;
    protected ShippingMethod $shippingMethod;
    protected Warehouse $warehouse;

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
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'location' => 'Test Location',
        ]);

        Inventory::create([
            'product_variant_id' => $this->variant->id,
            'warehouse_id' => $this->warehouse->id,
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

    public function test_can_get_orders_list(): void
    {
        // Create an order
        $order = PurchaseOrder::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'order_code' => 'ORD-TEST-001',
            'status' => 'pending',
            'shipping_recipient_name' => 'Test User',
            'shipping_recipient_phone' => '0123456789',
            'shipping_address' => 'Test Address',
            'shipping_province' => 'Hanoi',
            'sub_total' => 100000,
            'shipping_fee' => 50000,
            'discount_amount' => 0,
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'order_code', 'status', 'total_amount',
                        'shipping_recipient_name', 'created_at',
                    ],
                ],
                'current_page',
                'per_page',
            ]);
    }

    public function test_can_get_single_order(): void
    {
        $order = PurchaseOrder::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'order_code' => 'ORD-TEST-001',
            'status' => 'pending',
            'shipping_recipient_name' => 'Test User',
            'shipping_recipient_phone' => '0123456789',
            'shipping_address' => 'Test Address',
            'shipping_province' => 'Hanoi',
            'sub_total' => 100000,
            'shipping_fee' => 50000,
            'discount_amount' => 0,
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'order_code', 'status', 'shipping_recipient_name',
                'shipping_address', 'total_amount',
            ])
            ->assertJson([
                'id' => $order->id,
                'order_code' => $order->order_code,
            ]);
    }

    public function test_can_create_order_from_cart(): void
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
            ->postJson('/api/orders', [
                'shipping_recipient_name' => 'Test User',
                'shipping_recipient_phone' => '0123456789',
                'shipping_address' => '123 Test Street',
                'shipping_province' => 'Hanoi',
                'payment_method_id' => $this->paymentMethod->id,
                'shipping_method_id' => $this->shippingMethod->id,
                'notes' => 'Test order',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'order_code', 'status', 'total_amount',
            ]);

        // Verify order was created
        $this->assertDatabaseHas('purchase_orders', [
            'user_id' => $this->user->id,
            'shipping_recipient_name' => 'Test User',
        ]);

        // Verify cart was cleared
        $this->assertDatabaseMissing('cart_details', [
            'cart_id' => $cart->id,
        ]);
    }

    public function test_cannot_create_order_with_empty_cart(): void
    {
        Cart::create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_recipient_name' => 'Test User',
                'shipping_recipient_phone' => '0123456789',
                'shipping_address' => '123 Test Street',
                'shipping_province' => 'Hanoi',
                'payment_method_id' => $this->paymentMethod->id,
                'shipping_method_id' => $this->shippingMethod->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cart is empty',
            ]);
    }

    public function test_cannot_create_order_without_required_fields(): void
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartDetail::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'price' => $this->variant->price,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'shipping_recipient_name',
                'shipping_recipient_phone',
                'shipping_address',
                'shipping_province',
                'payment_method_id',
            ]);
    }

    public function test_can_cancel_pending_order(): void
    {
        $order = PurchaseOrder::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'order_code' => 'ORD-TEST-001',
            'status' => 'pending',
            'shipping_recipient_name' => 'Test User',
            'shipping_recipient_phone' => '0123456789',
            'shipping_address' => 'Test Address',
            'shipping_province' => 'Hanoi',
            'sub_total' => 100000,
            'shipping_fee' => 50000,
            'discount_amount' => 0,
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $order = PurchaseOrder::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'order_code' => 'ORD-TEST-001',
            'status' => 'completed',
            'shipping_recipient_name' => 'Test User',
            'shipping_recipient_phone' => '0123456789',
            'shipping_address' => 'Test Address',
            'shipping_province' => 'Hanoi',
            'sub_total' => 100000,
            'shipping_fee' => 50000,
            'discount_amount' => 0,
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot cancel order with current status',
            ]);
    }

    public function test_can_track_order_by_code(): void
    {
        $order = PurchaseOrder::create([
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
            'order_code' => 'ORD-TEST-TRACK',
            'status' => 'pending',
            'shipping_recipient_name' => 'Test User',
            'shipping_recipient_phone' => '0123456789',
            'shipping_address' => 'Test Address',
            'shipping_province' => 'Hanoi',
            'sub_total' => 100000,
            'shipping_fee' => 50000,
            'discount_amount' => 0,
            'total_amount' => 150000,
        ]);

        $response = $this->getJson("/api/orders/track/{$order->order_code}");

        $response->assertStatus(200)
            ->assertJson([
                'order_code' => 'ORD-TEST-TRACK',
            ]);
    }

    public function test_user_cannot_view_other_user_order(): void
    {
        $otherUser = User::factory()->create([
            'role_id' => Role::where('name', 'Customer')->first()->id,
        ]);

        $order = PurchaseOrder::create([
            'user_id' => $otherUser->id,
            'warehouse_id' => $this->warehouse->id,
            'order_code' => 'ORD-TEST-001',
            'status' => 'pending',
            'shipping_recipient_name' => 'Other User',
            'shipping_recipient_phone' => '0123456789',
            'shipping_address' => 'Test Address',
            'shipping_province' => 'Hanoi',
            'sub_total' => 100000,
            'shipping_fee' => 50000,
            'discount_amount' => 0,
            'total_amount' => 150000,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(404);
    }
}
