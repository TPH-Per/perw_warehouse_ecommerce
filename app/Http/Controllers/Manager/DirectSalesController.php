<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DirectSalesController extends Controller
{
    /**
     * Display a listing of direct sales
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['user', 'payment.paymentMethod', 'orderDetails'])
            ->whereNull('shipping_address'); // Direct sales have no shipping address

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Filter by date
        if ($request->has('date') && $request->date != '') {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->whereHas('payment', function($q) use ($request) {
                $q->where('payment_method_id', $request->payment_method);
            });
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate(20);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('manager.sales.index', compact('sales', 'paymentMethods'));
    }

    /**
     * Show the form for creating a new direct sale
     */
    public function create()
    {
        $warehouses = Warehouse::all();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('manager.sales.create', compact('warehouses', 'paymentMethods'));
    }

    /**
     * Store a newly created direct sale
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $warehouseId = $request->warehouse_id;
            $subTotal = 0;
            $orderItems = [];

            // Validate inventory and calculate totals
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->findOrFail($item['variant_id']);

                // Check inventory availability
                $inventory = Inventory::where('product_variant_id', $variant->id)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                if (!$inventory) {
                    throw new \Exception("Product {$variant->product->name} ({$variant->sku}) is not available in the selected warehouse.");
                }

                $availableQuantity = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                if ($availableQuantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$variant->product->name} ({$variant->sku}). Available: {$availableQuantity}, Requested: {$item['quantity']}");
                }

                $itemSubtotal = $variant->price * $item['quantity'];
                $subTotal += $itemSubtotal;

                $orderItems[] = [
                    'variant' => $variant,
                    'inventory' => $inventory,
                    'quantity' => $item['quantity'],
                    'price' => $variant->price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // Generate order code
            $orderCode = 'DS-' . now()->format('Ymd') . '-' . str_pad(PurchaseOrder::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create order (direct sale - no user, no shipping)
            $order = PurchaseOrder::create([
                'user_id' => null, // Walk-in customer
                'order_code' => $orderCode,
                'status' => 'delivered', // Direct sales are immediately delivered
                'shipping_recipient_name' => $request->customer_name ?? 'Walk-in Customer',
                'shipping_recipient_phone' => $request->customer_phone,
                'shipping_address' => null, // No shipping for direct sales
                'sub_total' => $subTotal,
                'shipping_fee' => 0, // No shipping fee
                'discount_amount' => 0,
                'total_amount' => $subTotal,
            ]);

            // Create order details and update inventory
            foreach ($orderItems as $item) {
                // Create order detail
                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant']->id,
                    'quantity' => $item['quantity'],
                    'price_at_purchase' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update inventory - reduce quantity on hand
                $item['inventory']->decrement('quantity_on_hand', $item['quantity']);

                // Create inventory transaction
                InventoryTransaction::create([
                    'product_variant_id' => $item['variant']->id,
                    'warehouse_id' => $warehouseId,
                    'order_id' => $order->id,
                    'transaction_type' => 'sale',
                    'quantity_change' => -$item['quantity'],
                    'quantity_after' => $item['inventory']->quantity_on_hand,
                    'notes' => "Direct sale - Order {$orderCode}",
                ]);
            }

            // Create payment
            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $subTotal,
                'status' => 'completed',
                'transaction_code' => 'DS-' . $order->id . '-' . now()->timestamp,
            ]);

            DB::commit();

            return redirect()->route('manager.sales.show', $order->id)
                ->with('success', 'Direct sale completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Direct sale failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to complete sale: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified direct sale
     */
    public function show(PurchaseOrder $order)
    {
        $order->load(['orderDetails.productVariant.product', 'payment.paymentMethod', 'inventoryTransactions']);

        return view('manager.sales.show', compact('order'));
    }

    /**
     * Get available products for a warehouse (API endpoint for AJAX)
     */
    public function getWarehouseProducts(Request $request)
    {
        $warehouseId = $request->warehouse_id;

        $products = Inventory::with(['productVariant.product'])
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_on_hand', '>', 0)
            ->get()
            ->map(function($inventory) {
                return [
                    'variant_id' => $inventory->productVariant->id,
                    'product_name' => $inventory->productVariant->product->name,
                    'variant_name' => $inventory->productVariant->name,
                    'sku' => $inventory->productVariant->sku,
                    'price' => $inventory->productVariant->price,
                    'available_quantity' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
                ];
            });

        return response()->json($products);
    }
}
