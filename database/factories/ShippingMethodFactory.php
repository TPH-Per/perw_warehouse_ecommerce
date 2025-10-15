<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Standard Shipping', 'Express Shipping', 'Overnight Shipping', 'International Shipping']),
            'cost' => $this->faker->randomFloat(2, 20000, 100000),
            'is_active' => true,
        ];
    }
}
