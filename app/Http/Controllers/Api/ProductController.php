<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier', 'variants.inventories', 'images'])
            ->whereIn('status', ['active', 'published']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by price range (using variants)
        if ($request->has('min_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price);
            });
        }

        if ($request->has('max_price')) {
            $query->whereHas('variants', function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price);
            });
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::with([
            'category',
            'supplier',
            'variants',
            'images',
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user')
                    ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Display the specified resource by slug.
     */
    public function showBySlug(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->with([
                'category',
                'supplier',
                'variants',
                'images',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->with('user')
                        ->orderBy('created_at', 'desc');
                }
            ])
            ->firstOrFail();

        return response()->json($product);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Product::with(['category', 'supplier', 'variants.inventories', 'images'])
            ->whereIn('status', ['active', 'published'])
            ->inRandomOrder()
            ->limit(8)
            ->get();

        return response()->json($products);
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        $products = Product::with(['category', 'supplier', 'variants.inventories', 'images'])
            ->whereIn('status', ['active', 'published'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();

        return response()->json($products);
    }
}
