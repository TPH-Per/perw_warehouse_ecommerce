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
        $placeholders = [];
        for ($i = 1; $i <= 12; $i++) {
            $placeholders[] = "/images/products/product-{$i}.svg";
        }

        return [
            'product_id' => Product::factory(),
            'image_url' => $this->faker->randomElement($placeholders),
            'is_primary' => $this->faker->boolean(50),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
