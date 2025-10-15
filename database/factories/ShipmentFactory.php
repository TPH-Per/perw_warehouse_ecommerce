<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\PurchaseOrder;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orders = PurchaseOrder::all();
        $shippingMethods = ShippingMethod::all();

        return [
            'order_id' => $orders->isNotEmpty() ? $orders->random()->id : PurchaseOrder::factory(),
            'shipping_method_id' => $shippingMethods->isNotEmpty() ? $shippingMethods->random()->id : ShippingMethod::factory(),
            'tracking_number' => $this->faker->uuid,
            'carrier' => $this->faker->company,
            'status' => $this->faker->randomElement(['pending', 'in_transit', 'delivered', 'failed']),
            'shipped_at' => $this->faker->dateTimeThisMonth,
            'estimated_delivery' => $this->faker->dateTimeThisMonth,
            'delivered_at' => $this->faker->dateTimeThisMonth,
        ];
    }
}
