<?php

namespace Database\Factories;

use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrder;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrderDetail>
 */
class PurchaseOrderDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get all orders and variants
        $orders = PurchaseOrder::pluck('id')->toArray();
        $variants = ProductVariant::pluck('id')->toArray();

        // Safety check for empty related data
        if (empty($orders) || empty($variants)) {
            return [
                'order_id' => 1,
                'product_variant_id' => 1,
                'quantity' => 1,
                'price_at_purchase' => 100000,
                'subtotal' => 100000,
            ];
        }

        // Try to find a unique combination, but limit attempts to prevent infinite loops
        $maxAttempts = 50;
        $attempts = 0;
        $order_id = null;
        $product_variant_id = null;
        $exists = true;

        do {
            $order_id = fake()->randomElement($orders);
            $product_variant_id = fake()->randomElement($variants);

            $exists = \App\Models\PurchaseOrderDetail::where('order_id', $order_id)
                ->where('product_variant_id', $product_variant_id)
                ->exists();

            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        // If we couldn't find a unique combination, create a simple one
        if ($exists) {
            // Find the first available combination
            $found = false;
            foreach ($orders as $orderId) {
                foreach ($variants as $variantId) {
                    if (!\App\Models\PurchaseOrderDetail::where('order_id', $orderId)
                        ->where('product_variant_id', $variantId)
                        ->exists()) {
                        $order_id = $orderId;
                        $product_variant_id = $variantId;
                        $found = true;
                        break 2;
                    }
                }
                if ($found) break;
            }

            // If still not found, just use the first ones
            if (!$found) {
                $order_id = $orders[0];
                $product_variant_id = $variants[0];
            }
        }

        $price = fake()->randomFloat(2, 10000, 5000000);
        $quantity = fake()->numberBetween(1, 10);

        return [
            'order_id' => $order_id,
            'product_variant_id' => $product_variant_id,
            'quantity' => $quantity,
            'price_at_purchase' => $price,
            'subtotal' => $price * $quantity,
        ];
    }
}
