<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity_on_hand' => $this->faker->numberBetween(0, 1000),
            'quantity_reserved' => $this->faker->numberBetween(0, 100),
            'reorder_level' => $this->faker->numberBetween(10, 50),
        ];
    }
}
