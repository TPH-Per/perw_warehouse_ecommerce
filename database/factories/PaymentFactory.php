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

        // Correctly create a PurchaseOrder if none exist and get its ID
        $orderId = $orders->isNotEmpty() 
            ? $orders->random()->id 
            : PurchaseOrder::factory()->create()->id; // Use ->create() to save and get ID

        // Correctly create a PaymentMethod if none exist and get its ID
        $paymentMethodId = $paymentMethods->isNotEmpty() 
            ? $paymentMethods->random()->id 
            : PaymentMethod::factory()->create()->id; // Use ->create() to save and get ID

        return [
            'order_id' => $orderId,
            'payment_method_id' => $paymentMethodId,
            'transaction_code' => $this->faker->uuid,
            'amount' => $this->faker->randomFloat(2, 10000, 10000000),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            // DO NOT set 'id' here; let the database auto-increment it.
        ];
    }
}