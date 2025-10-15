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
        $orders = PurchaseOrder::all();
        $productVariants = ProductVariant::all();

        $price = $this->faker->randomFloat(2, 10000, 5000000);
        $quantity = $this->faker->numberBetween(1, 10);

        return [
            'order_id' => $orders->isNotEmpty() ? $orders->random()->id : PurchaseOrder::factory(),
            'product_variant_id' => $productVariants->isNotEmpty() ? $productVariants->random()->id : ProductVariant::factory(),
            'quantity' => $quantity,
            'price_at_purchase' => $price,
            'subtotal' => $price * $quantity,
        ];
    }
}
