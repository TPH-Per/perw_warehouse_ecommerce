<?php

namespace Database\Factories;

use App\Models\InventoryTransaction;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random product variant and warehouse
        $productVariant = ProductVariant::inRandomOrder()->first() ?? ProductVariant::factory();
        $warehouse = Warehouse::inRandomOrder()->first() ?? Warehouse::factory();

        return [
            'product_variant_id' => is_object($productVariant) ? $productVariant->id : $productVariant,
            'warehouse_id' => is_object($warehouse) ? $warehouse->id : $warehouse,
            'type' => $this->faker->randomElement(['inbound', 'outbound']),
            'quantity' => $this->faker->numberBetween(-100, 100),
            'notes' => $this->faker->sentence,
        ];
    }
}
