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

class TestSeeder extends Seeder
{
    /**
     * Run a small test seed to verify functionality.
     */
    public function run(): void
    {
        echo "Starting test seed...\n";

        // Create a few users
        $users = User::factory()->count(5)->create([
            'role_id' => 2, // End User role
            'status' => 'active'
        ]);
        echo "Created 5 users\n";

        // Create a few products
        $categories = Category::whereNull('parent_id')->get();
        $suppliers = Supplier::all();

        $products = Product::factory()->count(5)->make();
        foreach ($products as $product) {
            $product->category_id = $categories->random()->id;
            $product->supplier_id = $suppliers->random()->id;
            $product->save();
        }
        echo "Created 5 products\n";

        // Create a few variants
        $productVariants = [];
        foreach ($products as $product) {
            $productVariants[] = ProductVariant::factory()->create([
                'product_id' => $product->id
            ]);
        }
        echo "Created 5 product variants\n";

        echo "Test seed completed successfully!\n";
    }
}
