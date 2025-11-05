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
            ->withCount('images as images_count')
            ->where('status', 'published')
            ->orderByDesc('images_count')
            ->orderByDesc('id');

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

        // Add stock_quantity to each variant
        $products->getCollection()->transform(function ($product) {
            if ($product->variants) {
                $product->variants->transform(function ($variant) {
                    $variant->stock_quantity = $variant->inventories->sum('quantity_on_hand');
                    return $variant;
                });
            }
            return $product;
        });

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
            'variants.inventories',
            'images',
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user')
                    ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Add stock_quantity to each variant
        if ($product->variants) {
            $product->variants->transform(function ($variant) {
                $variant->stock_quantity = $variant->inventories->sum('quantity_on_hand');
                return $variant;
            });
        }

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
                'variants.inventories',
                'images',
                'reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->with('user')
                        ->orderBy('created_at', 'desc');
                }
            ])
            ->firstOrFail();

        // Add stock_quantity to each variant
        if ($product->variants) {
            $product->variants->transform(function ($variant) {
                $variant->stock_quantity = $variant->inventories->sum('quantity_on_hand');
                return $variant;
            });
        }

        return response()->json($product);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Product::with(['category', 'supplier', 'variants.inventories', 'images'])
            ->withCount('images as images_count')
            ->where('status', 'published')
            ->orderByDesc('images_count')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        // Add stock_quantity to each variant
        $products->transform(function ($product) {
            if ($product->variants) {
                $product->variants->transform(function ($variant) {
                    $variant->stock_quantity = $variant->inventories->sum('quantity_on_hand');
                    return $variant;
                });
            }
            return $product;
        });

        return response()->json($products);
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');

        $products = Product::with(['category', 'supplier', 'variants.inventories', 'images'])
            ->withCount('images as images_count')
            ->where('status', 'published')
            ->orderByDesc('images_count')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            })
            ->limit(10)
            ->get();

        // Add stock_quantity to each variant
        $products->transform(function ($product) {
            if ($product->variants) {
                $product->variants->transform(function ($variant) {
                    $variant->stock_quantity = $variant->inventories->sum('quantity_on_hand');
                    return $variant;
                });
            }
            return $product;
        });

        return response()->json($products);
    }
}
