<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\User;
use App\Models\ProductVariant;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get end users for orders
        $endUsers = User::whereHas('role', function($query) {
            $query->where('name', 'endUser');
        })->get();

        if ($endUsers->isEmpty()) {
            echo "No end users found. Please run UserSeeder first.\n";
            return;
        }

        // Get product variants
        $productVariants = ProductVariant::all();

        if ($productVariants->isEmpty()) {
            echo "No product variants found. Please run AllModelsSeeder first.\n";
            return;
        }

        // Create 20 orders
        for ($i = 0; $i < 20; $i++) {
            // Select a random user
            $user = $endUsers->random();

            // Create order
            $order = PurchaseOrder::create([
                'user_id' => $user->id,
                'order_code' => 'PERW-' . strtoupper(fake()->lexify('????')) . fake()->numerify('####'),
                'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
                'shipping_recipient_name' => $user->full_name,
                'shipping_recipient_phone' => $user->phone_number ?? fake()->phoneNumber,
                'shipping_address' => fake()->address,
                'sub_total' => 0, // Will be updated after adding details
                'shipping_fee' => fake()->randomElement([0, 20000, 35000, 50000, 80000]),
                'discount_amount' => 0,
                'total_amount' => 0, // Will be updated after adding details
            ]);

            // Add 1-5 order details
            $detailCount = fake()->numberBetween(1, 5);
            $subTotal = 0;

            for ($j = 0; $j < $detailCount; $j++) {
                $variant = $productVariants->random();
                $quantity = fake()->numberBetween(1, 3);
                $price = $variant->price;
                $subtotal = $price * $quantity;

                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'price_at_purchase' => $price,
                    'subtotal' => $subtotal,
                ]);

                $subTotal += $subtotal;
            }

            // Update order with calculated totals
            $order->update([
                'sub_total' => $subTotal,
                'total_amount' => $subTotal + $order->shipping_fee - $order->discount_amount,
            ]);
        }

        echo "Orders created successfully.\n";
    }
}
