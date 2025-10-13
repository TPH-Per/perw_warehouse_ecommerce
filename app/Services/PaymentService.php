<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentService
{
    /**
     * Get available payment methods
     */
    public function getAvailablePaymentMethods(): Collection
    {
        return PaymentMethod::where('is_active', true)
                           ->orderBy('name')
                           ->get();
    }

    /**
     * Process payment for an order
     */
    public function processPayment(int $userId, int $orderId, array $paymentData): array
    {
        return DB::transaction(function () use ($userId, $orderId, $paymentData) {
            // Get the order
            $order = PurchaseOrder::where('id', $orderId)
                                 ->where('user_id', $userId)
                                 ->firstOrFail();

            // Validate order can be paid
            if (!$this->canProcessPayment($order)) {
                throw new \Exception('Order cannot be paid. This order is not in a payable state.');
            }

            // Check payment method is active
            $paymentMethod = PaymentMethod::where('id', $paymentData['payment_method_id'])
                                         ->where('is_active', true)
                                         ->firstOrFail();

            // Validate payment amount
            if ($paymentData['amount'] != $order->total_amount) {
                throw new \Exception('Payment amount must equal order total amount.');
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentData['payment_method_id'],
                'amount' => $paymentData['amount'],
                'status' => 'processing',
                'transaction_code' => $this->generateTransactionCode(),
            ]);

            // Process payment with provider
            $paymentResult = $this->processPaymentWithProvider($payment, $paymentMethod, $paymentData);

            if ($paymentResult['success']) {
                // Update payment status
                $payment->update([
                    'status' => 'completed',
                    'transaction_code' => $paymentResult['transaction_code'],
                ]);

                // Update order status
                $order->update(['status' => 'paid']);

                return [
                    'payment' => $payment->load('paymentMethod'),
                    'order' => $order,
                    'success' => true,
                    'message' => 'Payment processed successfully.'
                ];

            } else {
                // Payment failed
                $payment->update(['status' => 'failed']);
                
                return [
                    'payment' => $payment->load('paymentMethod'),
                    'order' => $order,
                    'success' => false,
                    'message' => 'Payment failed.',
                    'error' => $paymentResult['error_message']
                ];
            }
        });
    }

    /**
     * Get payment history for user
     */
    public function getUserPaymentHistory(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                     $q->where('user_id', $userId);
                 })
                 ->with(['purchaseOrder:id,order_code,total_amount', 'paymentMethod:id,name']);

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get payment details
     */
    public function getUserPayment(int $userId, int $paymentId): Payment
    {
        return Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                       $q->where('user_id', $userId);
                   })
                   ->with(['purchaseOrder', 'paymentMethod'])
                   ->findOrFail($paymentId);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(int $paymentId, float $refundAmount, string $reason = null): array
    {
        return DB::transaction(function () use ($paymentId, $refundAmount, $reason) {
            $payment = Payment::with('purchaseOrder')->findOrFail($paymentId);

            // Validate payment can be refunded
            if (!$this->canRefundPayment($payment, $refundAmount)) {
                throw new \Exception('Payment cannot be refunded or invalid refund amount.');
            }

            // Process refund with payment provider
            $refundResult = $this->processRefundWithProvider($payment, $refundAmount, $reason);

            if ($refundResult['success']) {
                // Update payment status or create refund record
                if ($refundAmount >= $payment->amount) {
                    // Full refund
                    $payment->update(['status' => 'refunded']);
                } else {
                    // Partial refund - you might want to create a separate refund record
                    $payment->update(['status' => 'partially_refunded']);
                }

                // Update order status if fully refunded
                if ($refundAmount >= $payment->amount) {
                    $payment->purchaseOrder->update(['status' => 'refunded']);
                }

                return [
                    'success' => true,
                    'refund_amount' => $refundAmount,
                    'transaction_code' => $refundResult['transaction_code'],
                    'message' => 'Refund processed successfully.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Refund failed.',
                    'error' => $refundResult['error_message']
                ];
            }
        });
    }

    /**
     * Get payment analytics (admin function)
     */
    public function getPaymentAnalytics(array $filters = []): array
    {
        $query = Payment::query();

        // Apply date filters
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $totalPayments = $query->count();
        $totalAmount = $query->where('status', 'completed')->sum('amount');
        $averagePayment = $totalPayments > 0 ? $totalAmount / $totalPayments : 0;

        $statusBreakdown = Payment::selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                                 ->groupBy('status')
                                 ->get()
                                 ->keyBy('status');

        $methodBreakdown = Payment::join('PaymentMethods', 'Payments.payment_method_id', '=', 'PaymentMethods.id')
                                 ->selectRaw('PaymentMethods.name, COUNT(*) as count, SUM(Payments.amount) as total_amount')
                                 ->groupBy('PaymentMethods.id', 'PaymentMethods.name')
                                 ->orderBy('count', 'desc')
                                 ->get();

        $dailyStats = Payment::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total_amount')
                            ->where('status', 'completed')
                            ->groupBy('date')
                            ->orderBy('date', 'desc')
                            ->limit(30)
                            ->get();

        return [
            'total_payments' => $totalPayments,
            'total_amount' => $totalAmount,
            'average_payment' => round($averagePayment, 2),
            'status_breakdown' => $statusBreakdown,
            'method_breakdown' => $methodBreakdown,
            'daily_statistics' => $dailyStats,
        ];
    }

    /**
     * Get all payments (admin function)
     */
    public function getAllPayments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::with(['purchaseOrder.user:id,full_name,email', 'paymentMethod']);

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment method
        if (isset($filters['payment_method_id'])) {
            $query->where('payment_method_id', $filters['payment_method_id']);
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_code', 'like', "%{$search}%");
                  });
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Check if payment can be processed
     */
    private function canProcessPayment(PurchaseOrder $order): bool
    {
        return $order->status === 'pending_payment';
    }

    /**
     * Check if payment can be refunded
     */
    private function canRefundPayment(Payment $payment, float $refundAmount): bool
    {
        if (!in_array($payment->status, ['completed', 'partially_refunded'])) {
            return false;
        }

        if ($refundAmount <= 0 || $refundAmount > $payment->amount) {
            return false;
        }

        return true;
    }

    /**
     * Generate unique transaction code
     */
    private function generateTransactionCode(): string
    {
        $prefix = 'TXN';
        $timestamp = now()->format('YmdHis');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        $transactionCode = $prefix . $timestamp . $random;
        
        // Ensure uniqueness
        while (Payment::where('transaction_code', $transactionCode)->exists()) {
            $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $transactionCode = $prefix . $timestamp . $random;
        }
        
        return $transactionCode;
    }

    /**
     * Process payment with external provider (mock implementation)
     */
    private function processPaymentWithProvider(Payment $payment, PaymentMethod $paymentMethod, array $paymentData): array
    {
        // This is a mock implementation
        // In a real application, you would integrate with actual payment providers
        // like Stripe, PayPal, VNPay, etc.
        
        // Simulate payment processing delay
        // sleep(1);
        
        // Simulate random success/failure for demo purposes
        $success = mt_rand(1, 100) <= 95; // 95% success rate
        
        if ($success) {
            return [
                'success' => true,
                'transaction_code' => 'TXN' . now()->format('YmdHis') . mt_rand(1000, 9999),
                'message' => 'Payment processed successfully'
            ];
        } else {
            return [
                'success' => false,
                'error_message' => 'Payment declined by provider',
                'transaction_code' => null
            ];
        }
    }

    /**
     * Process refund with external provider (mock implementation)
     */
    private function processRefundWithProvider(Payment $payment, float $refundAmount, ?string $reason): array
    {
        // This is a mock implementation
        // In a real application, you would integrate with actual payment providers
        
        // Simulate refund processing
        $success = mt_rand(1, 100) <= 90; // 90% success rate for refunds
        
        if ($success) {
            return [
                'success' => true,
                'transaction_code' => 'RFN' . now()->format('YmdHis') . mt_rand(1000, 9999),
                'message' => 'Refund processed successfully'
            ];
        } else {
            return [
                'success' => false,
                'error_message' => 'Refund failed - please try again later',
                'transaction_code' => null
            ];
        }
    }

    /**
     * Get payment by transaction code
     */
    public function getPaymentByTransactionCode(string $transactionCode): Payment
    {
        return Payment::where('transaction_code', $transactionCode)
                     ->with(['purchaseOrder', 'paymentMethod'])
                     ->firstOrFail();
    }

    /**
     * Update payment status (admin function)
     */
    public function updatePaymentStatus(int $paymentId, string $status): Payment
    {
        $payment = Payment::findOrFail($paymentId);
        
        $validStatuses = ['processing', 'completed', 'failed', 'refunded', 'partially_refunded'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid payment status.');
        }

        $payment->update(['status' => $status]);
        
        return $payment;
    }
}