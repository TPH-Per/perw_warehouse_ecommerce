<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    /**
     * Display inventory levels across all warehouses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'warehouse_id' => $request->get('warehouse_id'),
                'product_id' => $request->get('product_id'),
                'low_stock' => $request->get('low_stock'),
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by', 'updated_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = $request->get('per_page', 20);
            $inventories = $this->inventoryService->getInventoryLevels(array_filter($filters), $perPage);

            return response()->json([
                'inventories' => $inventories,
                'message' => 'Inventory levels retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve inventory levels.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display inventory for a specific product variant
     */
    public function show(int $productVariantId): JsonResponse
    {
        try {
            $result = $this->inventoryService->getProductVariantInventory($productVariantId);

            return response()->json([
                'product_variant' => $result['product_variant'],
                'inventories' => $result['inventories'],
                'summary' => $result['summary'],
                'message' => 'Inventory details retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product variant not found.',
                'errors' => ['product_variant' => ['The requested product variant does not exist.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve inventory details.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Adjust inventory levels (Admin only)
     */
    public function adjust(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_variant_id' => 'required|exists:ProductVariants,id',
                'warehouse_id' => 'required|exists:Warehouses,id',
                'adjustment_type' => 'required|in:addition,subtraction,set',
                'quantity' => 'required|integer|min:0',
                'reason' => 'required|string|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            $result = $this->inventoryService->adjustInventory($request->validated());

            return response()->json([
                'inventory' => $result['inventory'],
                'transaction' => $result['transaction'],
                'message' => 'Inventory adjusted successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to adjust inventory.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Inbound inventory (receiving stock)
     */
    public function inbound(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_variant_id' => 'required|exists:ProductVariants,id',
                'items.*.warehouse_id' => 'required|exists:Warehouses,id',
                'items.*.quantity' => 'required|integer|min:1',
                'supplier_reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500',
            ]);

            $processedItems = $this->inventoryService->processInbound(
                $request->input('items'),
                $request->input('supplier_reference'),
                $request->input('notes')
            );

            return response()->json([
                'processed_items' => $processedItems,
                'message' => 'Inventory inbound processed successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process inventory inbound.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get inventory statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $warehouseId = $request->get('warehouse_id');
            $statistics = $this->inventoryService->getStatistics($warehouseId);

            return response()->json([
                'overall_statistics' => $statistics['overall_statistics'],
                'warehouse_statistics' => $statistics['warehouse_statistics'],
                'message' => 'Inventory statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve inventory statistics.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get list of warehouses
     */
    public function warehouses(): JsonResponse
    {
        try {
            $warehouses = $this->inventoryService->getWarehouses();

            return response()->json([
                'warehouses' => $warehouses,
                'message' => 'Warehouses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve warehouses.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Check stock availability
     */
    public function checkStock(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_variant_id' => 'required|exists:ProductVariants,id',
                'quantity' => 'required|integer|min:1',
                'warehouse_id' => 'nullable|exists:Warehouses,id',
            ]);

            $result = $this->inventoryService->checkStockAvailability(
                $request->product_variant_id,
                $request->quantity,
                $request->warehouse_id
            );

            return response()->json([
                'availability' => $result,
                'message' => 'Stock availability checked successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to check stock availability.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}