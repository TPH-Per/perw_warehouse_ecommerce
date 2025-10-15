<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orders = PurchaseOrder::all();
        $paymentMethods = PaymentMethod::all();

        return [
            'order_id' => $orders->isNotEmpty() ? $orders->random()->id : PurchaseOrder::factory(),
            'payment_method_id' => $paymentMethods->isNotEmpty() ? $paymentMethods->random()->id : PaymentMethod::factory(),
            'transaction_id' => $this->faker->uuid,
            'amount' => $this->faker->randomFloat(2, 10000, 10000000),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'payment_date' => $this->faker->dateTimeThisYear,
        ];
    }
}
