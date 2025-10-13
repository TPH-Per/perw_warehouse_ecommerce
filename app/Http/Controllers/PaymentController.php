<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Get authenticated user ID
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }
        return $userId;
    }

    /**
     * Display available payment methods
     */
    public function methods(): JsonResponse
    {
        try {
            $paymentMethods = PaymentMethod::where('is_active', true)
                                         ->orderBy('name')
                                         ->get();

            return response()->json([
                'payment_methods' => $paymentMethods,
                'message' => 'Payment methods retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve payment methods.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Process payment for an order
     */
    public function processPayment(Request $request, int $orderId): JsonResponse
    {
        try {
            $request->validate([
                'payment_method_id' => 'required|exists:PaymentMethods,id',
                'amount' => 'required|numeric|min:0.01',
            ]);

            $userId = $this->getAuthenticatedUserId();
            
            // Get the order
            $order = PurchaseOrder::where('id', $orderId)
                                 ->where('user_id', $userId)
                                 ->firstOrFail();

            // Check if order can be paid
            if ($order->status !== 'pending_payment') {
                return response()->json([
                    'message' => 'Order cannot be paid.',
                    'errors' => ['order' => ['This order is not in a payable state.']]
                ], 422);
            }

            // Check payment method is active
            $paymentMethod = PaymentMethod::where('id', $request->payment_method_id)
                                         ->where('is_active', true)
                                         ->firstOrFail();

            // Validate payment amount
            if ($request->amount != $order->total_amount) {
                return response()->json([
                    'message' => 'Invalid payment amount.',
                    'errors' => ['amount' => ['Payment amount must equal order total amount.']]
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Create payment record
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount' => $request->amount,
                    'status' => 'processing',
                    'transaction_code' => $this->generateTransactionCode(),
                ]);

                // Simulate payment processing
                $paymentResult = $this->processPaymentWithProvider($payment, $paymentMethod);

                if ($paymentResult['success']) {
                    // Update payment status
                    $payment->update([
                        'status' => 'completed',
                        'transaction_code' => $paymentResult['transaction_code'],
                    ]);

                    // Update order status
                    $order->update(['status' => 'paid']);

                    DB::commit();

                    return response()->json([
                        'payment' => $payment->load('paymentMethod'),
                        'order' => $order,
                        'message' => 'Payment processed successfully.'
                    ]);

                } else {
                    // Payment failed
                    $payment->update(['status' => 'failed']);
                    
                    DB::commit();

                    return response()->json([
                        'message' => 'Payment failed.',
                        'errors' => ['payment' => [$paymentResult['error_message']]]
                    ], 422);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order or payment method not found.',
                'errors' => ['resource' => ['The requested resource does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment processing failed.',
                'errors' => ['server' => ['An unexpected error occurred during payment processing.']]
            ], 500);
        }
    }

    /**
     * Get payment history for user
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $query = Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                         $q->where('user_id', $userId);
                     })
                     ->with(['purchaseOrder:id,order_code,total_amount', 'paymentMethod:id,name']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $payments = $query->paginate($perPage);

            return response()->json([
                'payments' => $payments,
                'message' => 'Payment history retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve payment history.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function show(int $paymentId): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $payment = Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                           $q->where('user_id', $userId);
                       })
                       ->with(['purchaseOrder', 'paymentMethod'])
                       ->findOrFail($paymentId);

            return response()->json([
                'payment' => $payment,
                'message' => 'Payment details retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Payment not found.',
                'errors' => ['payment' => ['The requested payment does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve payment details.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get payment statistics for user
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $stats = Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                         $q->where('user_id', $userId);
                     })
                     ->selectRaw('
                         status,
                         COUNT(*) as count,
                         SUM(amount) as total_amount
                     ')
                     ->groupBy('status')
                     ->get()
                     ->keyBy('status');

            $totalPayments = Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                                 $q->where('user_id', $userId);
                             })->count();

            $totalPaid = Payment::whereHas('purchaseOrder', function ($q) use ($userId) {
                             $q->where('user_id', $userId);
                         })
                         ->where('status', 'completed')
                         ->sum('amount');

            return response()->json([
                'statistics' => [
                    'total_payments' => $totalPayments,
                    'total_paid' => $totalPaid,
                    'by_status' => $stats,
                ],
                'message' => 'Payment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve payment statistics.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Refund a payment (Admin only)
     */
    public function refund(Request $request, int $paymentId): JsonResponse
    {
        try {
            $request->validate([
                'refund_amount' => 'required|numeric|min:0.01',
                'reason' => 'required|string|max:255',
            ]);

            $payment = Payment::with(['purchaseOrder', 'paymentMethod'])
                             ->findOrFail($paymentId);

            // Check if payment can be refunded
            if ($payment->status !== 'completed') {
                return response()->json([
                    'message' => 'Payment cannot be refunded.',
                    'errors' => ['payment' => ['Only completed payments can be refunded.']]
                ], 422);
            }

            // Validate refund amount
            if ($request->refund_amount > $payment->amount) {
                return response()->json([
                    'message' => 'Invalid refund amount.',
                    'errors' => ['amount' => ['Refund amount cannot exceed payment amount.']]
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Process refund with payment provider
                $refundResult = $this->processRefundWithProvider($payment, $request->refund_amount, $request->reason);

                if ($refundResult['success']) {
                    // Update payment status
                    $payment->update(['status' => 'refunded']);

                    // Update order status if full refund
                    if ($request->refund_amount == $payment->amount) {
                        $payment->purchaseOrder->update(['status' => 'refunded']);
                    }

                    DB::commit();

                    return response()->json([
                        'payment' => $payment,
                        'refund_amount' => $request->refund_amount,
                        'refund_transaction_code' => $refundResult['transaction_code'],
                        'message' => 'Payment refunded successfully.'
                    ]);

                } else {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Refund failed.',
                        'errors' => ['refund' => [$refundResult['error_message']]]
                    ], 422);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Payment not found.',
                'errors' => ['payment' => ['The requested payment does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Refund processing failed.',
                'errors' => ['server' => ['An unexpected error occurred during refund processing.']]
            ], 500);
        }
    }

    /**
     * Generate unique transaction code
     */
    private function generateTransactionCode(): string
    {
        return 'TXN' . now()->format('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Simulate payment processing with external provider
     */
    private function processPaymentWithProvider(Payment $payment, PaymentMethod $paymentMethod): array
    {
        // This is a simulation - in real implementation, you would integrate with actual payment gateways
        // like Stripe, PayPal, VNPay, etc.
        
        // Simulate processing delay
        usleep(500000); // 0.5 seconds

        // Simulate 95% success rate
        $success = mt_rand(1, 100) <= 95;
        
        if ($success) {
            return [
                'success' => true,
                'transaction_code' => 'PAY' . now()->format('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
            ];
        } else {
            return [
                'success' => false,
                'error_message' => 'Payment declined by provider.',
            ];
        }
    }

    /**
     * Simulate refund processing with external provider
     */
    private function processRefundWithProvider(Payment $payment, float $amount, string $reason): array
    {
        // This is a simulation - in real implementation, you would integrate with actual payment gateways
        
        // Simulate processing delay
        usleep(300000); // 0.3 seconds

        // Simulate 98% success rate for refunds
        $success = mt_rand(1, 100) <= 98;
        
        if ($success) {
            return [
                'success' => true,
                'transaction_code' => 'REF' . now()->format('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT),
            ];
        } else {
            return [
                'success' => false,
                'error_message' => 'Refund request rejected by provider.',
            ];
        }
    }
}