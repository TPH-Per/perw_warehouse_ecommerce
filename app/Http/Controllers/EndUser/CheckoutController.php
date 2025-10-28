<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Shipment;
use App\Models\ShippingMethod;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{

    public function show(Request $request)
    {
        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load(['cartDetails.variant.product.images' => function ($q) {
            $q->where('is_primary', true);
        }]);

        $paymentMethods = PaymentMethod::query()->get();
        $shippingMethods = ShippingMethod::query()->get();

        $subTotal = $cart->cartDetails->sum(fn($d) => ($d->price ?? 0) * $d->quantity);
        $shippingFee = 50000; // đơn giản hóa như API
        $discountAmount = 0;
        $totalAmount = $subTotal + $shippingFee - $discountAmount;

        return view('enduser.checkout', compact('cart', 'paymentMethods', 'shippingMethods', 'subTotal', 'shippingFee', 'discountAmount', 'totalAmount'));
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'shipping_recipient_name' => 'required|string|max:255',
            'shipping_recipient_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $cartDetails = CartDetail::where('cart_id', $cart->id)->with(['variant.inventories'])->get();
        if ($cartDetails->isEmpty()) {
            return redirect()->route('enduser.cart')->withErrors(['cart' => 'Giỏ hàng trống']);
        }

        DB::beginTransaction();
        try {
            // Kiểm tra tồn kho
            foreach ($cartDetails as $detail) {
                /** @var ProductVariant $variant */
                $variant = $detail->variant;
                $available = $variant->inventories->sum(function ($inv) {
                    return max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0));
                });
                if ($available < $detail->quantity) {
                    throw new \Exception("Tồn kho không đủ cho SKU {$variant->sku}. Còn: {$available}, cần: {$detail->quantity}");
                }
            }

            // Tính tiền
            $subTotal = $cartDetails->sum(fn($d) => ($d->price ?? 0) * $d->quantity);
            $shippingFee = 50000;
            $discountAmount = 0;
            $totalAmount = $subTotal + $shippingFee - $discountAmount;

            // Mã đơn hàng
            $orderCode = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

            // Tạo đơn hàng
            $order = PurchaseOrder::create([
                'user_id' => $user->id,
                'order_code' => $orderCode,
                'status' => 'pending',
                'shipping_recipient_name' => $data['shipping_recipient_name'],
                'shipping_recipient_phone' => $data['shipping_recipient_phone'],
                'shipping_address' => $data['shipping_address'],
                'sub_total' => $subTotal,
                'shipping_fee' => $shippingFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            foreach ($cartDetails as $detail) {
                PurchaseOrderDetail::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $detail->product_variant_id,
                    'quantity' => $detail->quantity,
                    'price_at_purchase' => $detail->price,
                    'subtotal' => ($detail->price ?? 0) * $detail->quantity,
                ]);

                // Trừ tồn kho
                foreach ($detail->variant->inventories as $inv) {
                    $available = max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0));
                    if ($available <= 0) continue;
                    $use = min($available, $detail->quantity);
                    $inv->quantity_on_hand = ($inv->quantity_on_hand ?? 0) - $use;
                    $inv->save();
                    $detail->quantity -= $use;
                    if ($detail->quantity <= 0) break;
                }
            }

            // Thanh toán & vận chuyển
            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $totalAmount,
                'status' => 'pending',
            ]);

            Shipment::create([
                'order_id' => $order->id,
                'shipping_method_id' => $data['shipping_method_id'] ?? 1,
                'status' => 'pending',
            ]);

            // Xóa giỏ hàng
            CartDetail::where('cart_id', $cart->id)->delete();

            DB::commit();

            return redirect()->route('enduser.order.confirmation', ['id' => $order->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['checkout' => 'Tạo đơn thất bại: ' . $e->getMessage()])->withInput();
        }
    }
}
