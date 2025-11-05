<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DirectSalesController extends Controller
{
    /**
     * Display a listing of direct sales
     */
    public function index(Request $request)
    {
        // Ensure required payment methods exist for filters and consistency
        $this->ensureStandardPaymentMethods();

        $query = PurchaseOrder::with(['user', 'payment.paymentMethod', 'orderDetails'])
            ->where(function($q){
                $q->whereNull('shipping_address')
                  ->orWhere('shipping_address', '=','')
                  ->orWhere('shipping_address', 'like', 'Direct sale%');
            }); // Direct sales have no shipping address

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
        // Limit warehouses to the manager's assigned warehouse when applicable
        $user = Auth::user();
        if ($user && $user->role && $user->role->name === 'manager' && $user->warehouse_id) {
            $warehouses = Warehouse::where('id', $user->warehouse_id)->get();
        } else {
            $warehouses = Warehouse::all();
        }
        // Ensure required payment methods exist before showing the form
        $this->ensureStandardPaymentMethods();
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
            // Enforce assigned warehouse for manager accounts
            $user = Auth::user();
            $warehouseId = $request->warehouse_id;
            if ($user && $user->role && $user->role->name === 'manager' && $user->warehouse_id) {
                // Override to assigned warehouse to prevent cross-warehouse access
                $warehouseId = (int) $user->warehouse_id;
            }
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

            // Resolve payment method (may be used for payment record/meta)
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

            // Generate order code
            $orderCode = 'DS-' . now()->format('Ymd') . '-' . str_pad(PurchaseOrder::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Create order (direct sale - walk-in customer, no shipping)
            $order = PurchaseOrder::create([
                'user_id' => Auth::id(), // Associate with current manager to satisfy NOT NULL schema
                'order_code' => $orderCode,
                // Direct sales are considered delivered immediately (no shipping flow)
                'status' => 'delivered',
                'shipping_recipient_name' => $request->customer_name ?? 'Walk-in Customer',
                'shipping_recipient_phone' => $request->customer_phone ?? '',
                'shipping_address' => 'Direct sale - no shipping', // Placeholder to avoid NOT NULL constraint
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
                    'type' => 'outbound',
                    'quantity' => -$item['quantity'],
                    'notes' => "Direct sale - Order {$orderCode}",
                ]);
            }

            // Create payment depending on method
            if ($paymentMethod->code === 'vnpay') {
                // Mark pending; then redirect to VNPAY QR page to complete
                Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'payment_method_id' => $paymentMethod->id,
                        'amount' => $subTotal,
                        'status' => 'pending',
                        'transaction_code' => null,
                    ]
                );

                DB::commit();
                return redirect()->route('payment.vnpay.create', ['order' => $order->id, 'qr' => 1]);
            } else {
                Payment::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount' => $subTotal,
                    'status' => 'completed',
                    'transaction_code' => strtoupper($paymentMethod->code) . '-' . $order->id . '-' . now()->timestamp,
                ]);
            }

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
        // Debug: Log the request
        Log::info('Warehouse products request', [
            'warehouse_id' => $request->warehouse_id,
            'user_authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'user_role' => Auth::check() ? Auth::user()->role->name ?? null : null
        ]);

        $warehouseId = $request->warehouse_id;
        // Enforce assigned warehouse for manager accounts on product lookup
        $user = Auth::user();
        if ($user && $user->role && $user->role->name === 'manager' && $user->warehouse_id) {
            $warehouseId = (int) $user->warehouse_id;
        }

        // Validate warehouse ID
        if (!$warehouseId) {
            Log::warning('No warehouse ID provided');
            return response()->json([]);
        }

        $inventories = Inventory::with(['productVariant.product'])
            ->where('warehouse_id', $warehouseId)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        $products = $inventories
            ->filter(function($inventory) {
                // Filter out inventory records without product variants
                return $inventory->productVariant !== null && $inventory->productVariant->product !== null;
            })
            ->map(function($inventory) {
                return [
                    'variant_id' => $inventory->productVariant->id,
                    'product_name' => $inventory->productVariant->product->name ?? 'Unknown Product',
                    'variant_name' => $inventory->productVariant->name ?? 'Unknown Variant',
                    'sku' => $inventory->productVariant->sku ?? 'N/A',
                    'price' => $inventory->productVariant->price ?? 0,
                    'available_quantity' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
                ];
            });

        // Debug: Log the response
        Log::info('Warehouse products response', [
            'warehouse_id' => $warehouseId,
            'user_id' => $user->id ?? null,
            'product_count' => $products->count(),
            'products' => $products
        ]);

        // Convert Collection to array to ensure proper JSON serialization
        return response()->json($products->values());
    }

    /**
     * Ensure baseline payment methods exist: Online and VNPAY.
     */
    private function ensureStandardPaymentMethods(): void
    {
        PaymentMethod::firstOrCreate(
            ['code' => 'online'],
            ['name' => 'Thanh toán trực tuyến', 'is_active' => true]
        );
        PaymentMethod::firstOrCreate(
            ['code' => 'vnpay'],
            ['name' => 'VNPAY', 'is_active' => true]
        );
    }
}
