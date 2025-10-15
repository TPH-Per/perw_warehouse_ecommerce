<?php

namespace Database\Factories;

use App\Models\InventoryTransaction;
use App\Models\Inventory;
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
        $inventories = Inventory::all();
        return [
            'inventory_id' => $inventories->isNotEmpty() ? $inventories->random()->id : Inventory::factory(),
            'transaction_type' => $this->faker->randomElement(['inbound', 'outbound', 'adjustment']),
            'quantity' => $this->faker->numberBetween(-100, 100),
            'reference_type' => $this->faker->randomElement(['order', 'purchase', 'adjustment']),
            'reference_id' => $this->faker->numberBetween(1, 1000),
            'notes' => $this->faker->sentence,
        ];
    }
}
