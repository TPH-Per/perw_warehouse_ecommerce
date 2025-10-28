<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();
        $orders = PurchaseOrder::where('user_id', $user->id)
            ->with(['orderDetails.variant.product.images', 'payment.paymentMethod', 'shipment'])
            ->orderByDesc('created_at')
            ->paginate(10);
        return view('enduser.orders', compact('orders'));
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = PurchaseOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['orderDetails.variant.product.images', 'payment.paymentMethod', 'shipment'])
            ->firstOrFail();
        return view('enduser.order_show', compact('order'));
    }

    public function confirmation(Request $request, $id)
    {
        return $this->show($request, $id);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $order = PurchaseOrder::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return back()->withErrors(['order' => 'Không thể hủy ở trạng thái hiện tại']);
        }

        DB::beginTransaction();
        try {
            foreach ($order->orderDetails as $detail) {
                $variant = $detail->variant()->with('inventories')->first();
                if (!$variant) continue;
                $qty = $detail->quantity;
                foreach ($variant->inventories as $inv) {
                    $inv->quantity_on_hand = ($inv->quantity_on_hand ?? 0) + $qty;
                    $inv->save();
                    break;
                }
            }

            $order->status = 'cancelled';
            $order->save();
            if ($order->payment) {
                // Map cancelled order to a refundable payment outcome
                $order->payment->status = 'refunded';
                $order->payment->save();
            }

            DB::commit();
            return redirect()->route('enduser.orders')->with('success', 'Đã hủy đơn hàng');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['order' => 'Hủy đơn thất bại: ' . $e->getMessage()]);
        }
    }
}
