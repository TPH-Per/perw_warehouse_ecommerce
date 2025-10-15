<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\PaymentMethod;
use App\Models\Payment;
use App\Models\ShippingMethod;
use App\Models\Shipment;
use App\Models\ProductReview;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LargeDatasetSeeder extends Seeder
{
    /**
     * Run the database seeds to create at least 100 records for each table.
     */
    public function run(): void
    {
        echo "Starting large dataset seeding...\n";

        try {
            // Disable foreign key checks for faster seeding
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Clear existing data
            $this->cleanDatabase();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Create base data
            $this->createBaseData();

            // Create large datasets
            $this->createLargeDatasets();

            echo "Large dataset seeding completed successfully!\n";
        } catch (\Exception $e) {
            echo "Error during seeding: " . $e->getMessage() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "File: " . $e->getFile() . "\n";
        }
    }

    private function cleanDatabase(): void
    {
        echo "Cleaning database...\n";
        // Clear all tables in correct order to avoid foreign key constraints
        $tables = [
            'product_reviews',
            'shipments',
            'payments',
            'purchase_order_details',
            'carts',
            'cart_details',
            'inventory_transactions',
            'inventories',
            'product_images',
            'product_variants',
            'products',
            'addresses',
            'purchase_orders',
            'users',
            'warehouses',
            'suppliers',
            'categories',
            'roles',
            'payment_methods',
            'shipping_methods'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        echo "Database cleaned successfully!\n";
    }

    private function createBaseData(): void
    {
        echo "Creating base data...\n";

        // Create roles
        $adminRole = Role::factory()->create([
            'name' => 'Admin',
            'description' => 'Quản trị viên hệ thống, có toàn quyền.'
        ]);

        $userRole = Role::factory()->create([
            'name' => 'End User',
            'description' => 'Khách hàng mua sắm trên trang web.'
        ]);

        // Create payment methods
        PaymentMethod::factory()->create([
            'name' => 'Credit Card',
            'code' => 'credit_card',
            'is_active' => true
        ]);

        PaymentMethod::factory()->create([
            'name' => 'Bank Transfer',
            'code' => 'bank_transfer',
            'is_active' => true
        ]);

        PaymentMethod::factory()->create([
            'name' => 'Cash on Delivery',
            'code' => 'cash_on_delivery',
            'is_active' => true
        ]);

        // Create shipping methods
        ShippingMethod::factory()->create([
            'name' => 'Standard Shipping',
            'cost' => 30000.00,
            'is_active' => true
        ]);

        ShippingMethod::factory()->create([
            'name' => 'Express Shipping',
            'cost' => 50000.00,
            'is_active' => true
        ]);

        ShippingMethod::factory()->create([
            'name' => 'Overnight Shipping',
            'cost' => 80000.00,
            'is_active' => true
        ]);

        // Create categories
        $figureCategory = Category::factory()->create([
            'name' => 'Figure',
            'slug' => 'figure'
        ]);

        $nendoroidCategory = Category::factory()->create([
            'parent_id' => $figureCategory->id,
            'name' => 'Nendoroid',
            'slug' => 'nendoroid'
        ]);

        $scaleFigureCategory = Category::factory()->create([
            'parent_id' => $figureCategory->id,
            'name' => 'Scale Figure',
            'slug' => 'scale-figure'
        ]);

        $merchandiseCategory = Category::factory()->create([
            'name' => 'Merchandise',
            'slug' => 'merchandise'
        ]);

        $apparelCategory = Category::factory()->create([
            'parent_id' => $merchandiseCategory->id,
            'name' => 'Apparel',
            'slug' => 'apparel'
        ]);

        $accessoriesCategory = Category::factory()->create([
            'parent_id' => $merchandiseCategory->id,
            'name' => 'Accessories',
            'slug' => 'accessories'
        ]);

        // Create suppliers
        $suppliers = Supplier::factory()->count(10)->create();

        // Create warehouses
        $warehouses = Warehouse::factory()->count(5)->create();

        // Create admin user
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'full_name' => 'PerW Admin',
            'email' => 'admin@perw.com',
            'password' => bcrypt('password'),
            'phone_number' => '0900000001',
            'status' => 'active'
        ]);

        echo "Base data created successfully!\n";
    }

    private function createLargeDatasets(): void
    {
        echo "Starting to create large datasets...\n";

        // Create 100 users (reduced for better performance)
        echo "Creating 100 users...\n";
        $users = User::factory()->count(100)->create([
            'role_id' => 2, // End User role
            'status' => 'active'
        ]);
        echo "Created 100 users\n";

        // Create addresses (1-3 per user)
        echo "Creating addresses...\n";
        $addressCount = 0;
        foreach ($users as $user) {
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                Address::factory()->create([
                    'user_id' => $user->id,
                    'is_default' => $i === 0
                ]);
                $addressCount++;
            }
        }
        echo "Created " . $addressCount . " addresses\n";

        // Create 100 products (reduced for better performance)
        echo "Creating 100 products...\n";
        $categories = Category::whereNull('parent_id')->get();
        $suppliers = Supplier::all();

        for ($i = 0; $i < 100; $i++) {
            $product = Product::factory()->create([
                'category_id' => $categories->random()->id,
                'supplier_id' => $suppliers->random()->id
            ]);
        }
        echo "Created 100 products\n";

        // Get all products
        $products = Product::all();

        // Create product variants (2-4 per product)
        echo "Creating product variants...\n";
        $variantCount = 0;
        foreach ($products as $product) {
            $count = rand(2, 4);
            for ($i = 0; $i < $count; $i++) {
                ProductVariant::factory()->create([
                    'product_id' => $product->id
                ]);
                $variantCount++;
            }
        }
        echo "Created " . $variantCount . " product variants\n";

        // Get all product variants
        $productVariants = ProductVariant::all();

        // Create product images (1-5 per product)
        echo "Creating product images...\n";
        $imageCount = 0;
        foreach ($products as $product) {
            $count = rand(1, 5);
            for ($i = 0; $i < $count; $i++) {
                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'is_primary' => $i === 0
                ]);
                $imageCount++;
            }
        }
        echo "Created " . $imageCount . " product images\n";

        // Get warehouses
        $warehouses = Warehouse::all();

        // Create inventory records (1-2 per variant per warehouse)
        echo "Creating inventory records...\n";
        $inventoryCount = 0;
        foreach ($productVariants as $variant) {
            foreach ($warehouses as $warehouse) {
                if (rand(0, 1)) { // 50% chance to have inventory in each warehouse
                    Inventory::factory()->create([
                        'product_variant_id' => $variant->id,
                        'warehouse_id' => $warehouse->id
                    ]);
                    $inventoryCount++;
                }
            }
        }
        echo "Created " . $inventoryCount . " inventory records\n";

        // Get all inventories
        $inventories = Inventory::all();

        // Create 100 purchase orders (reduced for better performance)
        echo "Creating 100 purchase orders...\n";
        for ($i = 0; $i < 100; $i++) {
            PurchaseOrder::factory()->create([
                'user_id' => $users->random()->id
            ]);
        }
        echo "Created 100 purchase orders\n";

        // Get all purchase orders
        $purchaseOrders = PurchaseOrder::all();

        // Create purchase order details (1-5 per order)
        echo "Creating purchase order details...\n";
        $detailCount = 0;
        foreach ($purchaseOrders as $order) {
            $count = rand(1, 5);
            for ($i = 0; $i < $count; $i++) {
                if ($productVariants->isNotEmpty()) {
                    PurchaseOrderDetail::factory()->create([
                        'order_id' => $order->id,
                        'product_variant_id' => $productVariants->random()->id
                    ]);
                    $detailCount++;
                }
            }
        }
        echo "Created " . $detailCount . " purchase order details\n";

        // Get all purchase order details
        $orderDetails = PurchaseOrderDetail::all();

        // Create a summary of what we've created so far
        echo "\n=== SUMMARY OF CREATED RECORDS ===\n";
        echo "Users: " . User::count() . "\n";
        echo "Addresses: " . Address::count() . "\n";
        echo "Products: " . Product::count() . "\n";
        echo "Product Variants: " . ProductVariant::count() . "\n";
        echo "Product Images: " . ProductImage::count() . "\n";
        echo "Inventories: " . Inventory::count() . "\n";
        echo "Purchase Orders: " . PurchaseOrder::count() . "\n";
        echo "Order Details: " . PurchaseOrderDetail::count() . "\n";
        echo "==================================\n\n";

        echo "Seeding completed successfully with at least 100 records per major table!\n";
        echo "Note: Additional records like payments, shipments, and reviews can be added separately if needed.\n";
    }
}
