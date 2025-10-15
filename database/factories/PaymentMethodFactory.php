<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Credit Card', 'Debit Card', 'Bank Transfer', 'PayPal', 'Cash on Delivery']);
        return [
            'name' => $name,
            'code' => strtolower(str_replace(' ', '_', $name)),
            'is_active' => true,
        ];
    }
}
