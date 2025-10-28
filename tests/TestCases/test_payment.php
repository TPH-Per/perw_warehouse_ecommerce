<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchaseOrder;
use App\Models\PaymentMethod;
use App\Models\Payment;

try {
    // Get first order and payment method
    $order = PurchaseOrder::first();
    $method = PaymentMethod::first();

    if ($order && $method) {
        // Create a payment
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'transaction_id' => 'test123',
            'amount' => 100000,
            'status' => 'completed',
            'payment_date' => now()
        ]);

        echo "Payment created successfully!\n";
        echo "Payment ID: " . $payment->id . "\n";
    } else {
        echo "No order or payment method found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
