<?php

namespace App\Http\Controllers;

use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class WishlistController extends Controller
{
    protected $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->middleware('auth:sanctum');
        $this->wishlistService = $wishlistService;
    }

    /**
     * Get user's wishlist
     */
    public function index()
    {
        $wishlist = $this->wishlistService->getUserWishlist(Auth::id());
        
        return response()->json([
            'success' => true,
            'wishlist' => $wishlist,
            'count' => $wishlist->count()
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:Products,id'
        ]);

        $result = $this->wishlistService->addToWishlist(
            Auth::id(),
            $request->product_id
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Remove product from wishlist
     */
    public function destroy($productId)
    {
        $result = $this->wishlistService->removeFromWishlist(
            Auth::id(),
            $productId
        );

        return response()->json($result);
    }

    /**
     * Toggle product in wishlist
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:Products,id'
        ]);

        $result = $this->wishlistService->toggleWishlist(
            Auth::id(),
            $request->product_id
        );

        return response()->json($result);
    }

    /**
     * Get wishlist count
     */
    public function count()
    {
        $count = $this->wishlistService->getWishlistCount(Auth::id());
        
        return response()->json(['count' => $count]);
    }

    /**
     * Move wishlist items to cart
     */
    public function moveToCart(Request $request)
    {
        $productIds = $request->input('product_ids', []);
        
        $result = $this->wishlistService->moveToCart(Auth::id(), $productIds);
        
        return response()->json($result);
    }

    /**
     * Clear wishlist
     */
    public function clear()
    {
        $count = $this->wishlistService->clearWishlist(Auth::id());
        
        return response()->json([
            'success' => true,
            'message' => 'Wishlist cleared successfully',
            'cleared_count' => $count
        ]);
    }
}
