<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'name' => $this->faker->word,
            'sku' => $this->faker->unique()->ean8,
            'price' => $this->faker->randomFloat(2, 10000, 1000000),
            'original_price' => $this->faker->randomFloat(2, 10000, 1000000),
        ];
    }
}
