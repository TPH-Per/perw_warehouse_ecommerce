<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ShipmentController extends Controller
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
     * Display user's shipments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $query = Shipment::whereHas('purchaseOrder', function ($q) use ($userId) {
                         $q->where('user_id', $userId);
                     })
                     ->with(['purchaseOrder:id,order_code,total_amount', 'shippingMethod:id,name']);

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
            $shipments = $query->paginate($perPage);

            return response()->json([
                'shipments' => $shipments,
                'message' => 'Shipments retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve shipments.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified shipment
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $shipment = Shipment::whereHas('purchaseOrder', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->with(['purchaseOrder.details.productVariant.product', 'shippingMethod'])
                        ->findOrFail($id);

            return response()->json([
                'shipment' => $shipment,
                'message' => 'Shipment details retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Shipment not found.',
                'errors' => ['shipment' => ['The requested shipment does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve shipment details.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Track shipment by tracking code
     */
    public function track(string $trackingCode): JsonResponse
    {
        try {
            $shipment = Shipment::where('tracking_code', $trackingCode)
                               ->with(['purchaseOrder:id,order_code,shipping_recipient_name,shipping_address', 'shippingMethod:id,name'])
                               ->firstOrFail();

            // Check if user owns this shipment
            $userId = $this->getAuthenticatedUserId();
            if ($shipment->purchaseOrder->user_id !== $userId) {
                return response()->json([
                    'message' => 'Unauthorized access.',
                    'errors' => ['shipment' => ['You are not authorized to track this shipment.']]
                ], 403);
            }

            // Simulate tracking information
            $trackingInfo = $this->getTrackingInformation($shipment);

            return response()->json([
                'shipment' => $shipment,
                'tracking_info' => $trackingInfo,
                'message' => 'Shipment tracking information retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tracking code not found.',
                'errors' => ['tracking_code' => ['The provided tracking code does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve tracking information.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Create shipment for order (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:PurchaseOrders,id',
                'shipping_method_id' => 'required|exists:ShippingMethods,id',
            ]);

            $order = PurchaseOrder::findOrFail($request->order_id);

            // Check if order can be shipped
            if (!in_array($order->status, ['paid', 'processing'])) {
                return response()->json([
                    'message' => 'Order cannot be shipped.',
                    'errors' => ['order' => ['Only paid or processing orders can be shipped.']]
                ], 422);
            }

            // Check if shipment already exists
            if ($order->shipment) {
                return response()->json([
                    'message' => 'Shipment already exists for this order.',
                    'errors' => ['order' => ['This order already has a shipment record.']]
                ], 422);
            }

            $shipment = Shipment::create([
                'order_id' => $request->order_id,
                'shipping_method_id' => $request->shipping_method_id,
                'tracking_code' => $this->generateTrackingCode(),
                'status' => 'pending',
            ]);

            // Update order status
            $order->update(['status' => 'shipped']);

            return response()->json([
                'shipment' => $shipment->load(['purchaseOrder', 'shippingMethod']),
                'message' => 'Shipment created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create shipment.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update shipment status (Admin only)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in_transit,out_for_delivery,delivered,failed,returned',
            ]);

            $shipment = Shipment::with('purchaseOrder')->findOrFail($id);
            $oldStatus = $shipment->status;
            
            $shipment->update(['status' => $request->status]);

            // Update order status based on shipment status
            if ($request->status === 'delivered') {
                $shipment->purchaseOrder->update(['status' => 'completed']);
            } elseif ($request->status === 'failed' || $request->status === 'returned') {
                $shipment->purchaseOrder->update(['status' => 'shipping_failed']);
            }

            return response()->json([
                'shipment' => $shipment->load(['purchaseOrder', 'shippingMethod']),
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'message' => 'Shipment status updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Shipment not found.',
                'errors' => ['shipment' => ['The requested shipment does not exist.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update shipment status.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get available shipping methods
     */
    public function shippingMethods(): JsonResponse
    {
        try {
            $shippingMethods = ShippingMethod::where('is_active', true)
                                           ->orderBy('name')
                                           ->get();

            return response()->json([
                'shipping_methods' => $shippingMethods,
                'message' => 'Shipping methods retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve shipping methods.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get shipment statistics (Admin only)
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $stats = Shipment::selectRaw('
                         status,
                         COUNT(*) as count
                     ')
                     ->groupBy('status')
                     ->get()
                     ->keyBy('status');

            $totalShipments = Shipment::count();
            $completedShipments = Shipment::where('status', 'delivered')->count();
            $pendingShipments = Shipment::where('status', 'pending')->count();
            $inTransitShipments = Shipment::where('status', 'in_transit')->count();

            return response()->json([
                'statistics' => [
                    'total_shipments' => $totalShipments,
                    'completed_shipments' => $completedShipments,
                    'pending_shipments' => $pendingShipments,
                    'in_transit_shipments' => $inTransitShipments,
                    'by_status' => $stats,
                ],
                'message' => 'Shipment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve shipment statistics.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Generate unique tracking code
     */
    private function generateTrackingCode(): string
    {
        return 'TRK' . now()->format('YmdHis') . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Simulate tracking information
     */
    private function getTrackingInformation(Shipment $shipment): array
    {
        // This is a simulation - in real implementation, you would integrate with actual shipping providers
        
        $trackingEvents = [];
        $currentStatus = $shipment->status;
        
        // Base events that always exist
        $trackingEvents[] = [
            'status' => 'pending',
            'description' => 'Shipment created and pending pickup',
            'timestamp' => $shipment->created_at,
            'location' => 'Warehouse'
        ];

        // Add events based on current status
        if (in_array($currentStatus, ['in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'])) {
            $trackingEvents[] = [
                'status' => 'in_transit',
                'description' => 'Package is in transit',
                'timestamp' => $shipment->created_at->addHours(2),
                'location' => 'Sorting Facility'
            ];
        }

        if (in_array($currentStatus, ['out_for_delivery', 'delivered'])) {
            $trackingEvents[] = [
                'status' => 'out_for_delivery',
                'description' => 'Package is out for delivery',
                'timestamp' => $shipment->updated_at->subHours(2),
                'location' => 'Local Delivery Hub'
            ];
        }

        if ($currentStatus === 'delivered') {
            $trackingEvents[] = [
                'status' => 'delivered',
                'description' => 'Package delivered successfully',
                'timestamp' => $shipment->updated_at,
                'location' => 'Delivery Address'
            ];
        }

        return [
            'tracking_code' => $shipment->tracking_code,
            'current_status' => $currentStatus,
            'estimated_delivery' => $shipment->created_at->addDays(3)->format('Y-m-d'),
            'events' => $trackingEvents
        ];
    }
}