<?php

namespace Database\Factories;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'image_url' => $this->faker->imageUrl(640, 480, 'products'),
            'is_primary' => $this->faker->boolean(30), // 30% chance of being primary
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
