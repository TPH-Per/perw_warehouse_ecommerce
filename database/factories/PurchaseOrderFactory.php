<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::all();
        return [
            'user_id' => $users->isNotEmpty() ? $users->random()->id : User::factory(),
            'order_code' => 'PERW-' . $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'status' => $this->faker->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'shipping_recipient_name' => $this->faker->name,
            'shipping_recipient_phone' => $this->faker->phoneNumber,
            'shipping_address' => $this->faker->address,
            'sub_total' => $this->faker->randomFloat(2, 10000, 1000000),
            'shipping_fee' => $this->faker->randomFloat(2, 20000, 50000),
            'discount_amount' => 0,
            'total_amount' => $this->faker->randomFloat(2, 10000, 1000000),
        ];
    }
}
