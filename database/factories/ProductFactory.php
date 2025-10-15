<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => \App\Models\Category::factory(),
            'supplier_id' => \App\Models\Supplier::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'slug' => $this->faker->unique()->slug,
            'status' => 'published',
        ];
    }
}
