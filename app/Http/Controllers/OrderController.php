<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
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
     * Display user's order history
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $filters = [
                'status' => $request->get('status'),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = $request->get('per_page', 10);
            $orders = $this->orderService->getUserOrders($userId, array_filter($filters), $perPage);

            return response()->json([
                'orders' => $orders,
                'message' => 'Orders retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve orders.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $order = $this->orderService->getUserOrder($userId, $id);

            return response()->json([
                'order' => $order,
                'message' => 'Order retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found.',
                'errors' => ['order' => ['The requested order does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Create order from cart
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'shipping_recipient_name' => 'required|string|max:255',
                'shipping_recipient_phone' => 'required|string|max:20',
                'shipping_address' => 'required|string',
                'notes' => 'nullable|string|max:500',
                'discount_amount' => 'nullable|numeric|min:0',
            ]);

            $userId = $this->getAuthenticatedUserId();
            $result = $this->orderService->createOrderFromCart($userId, $request->validated());

            return response()->json([
                'order' => $result['order'],
                'message' => $result['message']
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cart not found.',
                'errors' => ['cart' => ['Your cart is empty or does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'empty') || 
                str_contains($e->getMessage(), 'stock') || 
                str_contains($e->getMessage(), 'available')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['order' => [$e->getMessage()]]
                ], 422);
            }
            
            return response()->json([
                'message' => 'Failed to create order.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Cancel an order (only if pending_payment)
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $order = $this->orderService->cancelOrder($userId, $id);

            return response()->json([
                'order' => $order,
                'message' => 'Order cancelled successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found.',
                'errors' => ['order' => ['The requested order does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Cannot cancel')) {
                return response()->json([
                    'message' => 'Cannot cancel order.',
                    'errors' => ['order' => [$e->getMessage()]]
                ], 422);
            }
            
            return response()->json([
                'message' => 'Failed to cancel order.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get order statistics for user
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            $statistics = $this->orderService->getUserOrderStatistics($userId);

            return response()->json([
                'statistics' => $statistics,
                'message' => 'Order statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order statistics.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}