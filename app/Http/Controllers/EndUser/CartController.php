<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // Middleware is applied in routes/web.php via route group

    public function index(Request $request)
    {
        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->load(['cartDetails.variant.product.images' => function ($q) {
            $q->where('is_primary', true);
        }]);

        $totalItems = $cart->cartDetails->sum('quantity');
        $totalAmount = $cart->cartDetails->sum(fn($d) => ($d->price ?? 0) * $d->quantity);

        return view('enduser.cart', compact('cart', 'totalItems', 'totalAmount'));
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $variant = ProductVariant::with('inventories')->findOrFail($data['product_variant_id']);

        $available = $variant->inventories->sum(function ($inv) {
            return max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0));
        });

        if ($available < $data['quantity']) {
            return back()->withErrors(['quantity' => 'Sản phẩm không đủ tồn kho.'])->withInput();
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $detail = CartDetail::withTrashed()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $data['product_variant_id'])
            ->first();

        if ($detail) {
            if ($detail->trashed()) {
                // revive the soft-deleted row to avoid unique conflicts
                $newQty = $data['quantity'];
                if ($available < $newQty) {
                    return back()->withErrors(['quantity' => 'Sản phẩm không đủ tồn kho.'])->withInput();
                }
                $detail->restore();
                $detail->quantity = $newQty;
                $detail->price = $variant->price;
                $detail->save();
            } else {
                $newQty = $detail->quantity + $data['quantity'];
                if ($available < $newQty) {
                    return back()->withErrors(['quantity' => 'Sản phẩm không đủ tồn kho.'])->withInput();
                }
                $detail->quantity = $newQty;
                $detail->price = $variant->price;
                $detail->save();
            }
        } else {
            CartDetail::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $data['product_variant_id'],
                'quantity' => $data['quantity'],
                'price' => $variant->price,
            ]);
        }

        return redirect()->route('enduser.cart')->with('success', 'Đã thêm vào giỏ hàng');
    }

    public function update(Request $request, $detailId)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $detail = CartDetail::where('cart_id', $cart->id)->where('id', $detailId)->firstOrFail();

        if ($data['quantity'] == 0) {
            $detail->delete();
        } else {
            $variant = ProductVariant::with('inventories')->findOrFail($detail->product_variant_id);
            $available = $variant->inventories->sum(function ($inv) {
                return max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0));
            });
            if ($available < $data['quantity']) {
                return back()->withErrors(['quantity' => 'Sản phẩm không đủ tồn kho.']);
            }
            $detail->quantity = $data['quantity'];
            $detail->save();
        }

        return redirect()->route('enduser.cart');
    }

    public function remove(Request $request, $detailId)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        $detail = CartDetail::where('cart_id', $cart->id)->where('id', $detailId)->firstOrFail();
        $detail->delete();
        return redirect()->route('enduser.cart')->with('success', 'Đã xóa sản phẩm khỏi giỏ');
    }
}
