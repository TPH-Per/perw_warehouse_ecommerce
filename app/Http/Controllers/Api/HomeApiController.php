<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeApiController extends Controller
{
    /**
     * Get products list with filters and sorting
     */
    public function index(Request $request)
    {
        $query = Product::with(['images' => function ($q) {
            $q->orderByDesc('is_primary');
        }, 'variants.inventories', 'category'])
            ->where('status', 'published');

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // Search by name
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where('name', 'like', "%{$q}%");
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'price_low':
                // Sort by minimum variant price (ascending)
                $query->withMin('variants', 'price')->orderBy('variants_min_price');
                break;
            case 'price_high':
                // Sort by minimum variant price (descending)
                $query->withMin('variants', 'price')->orderByDesc('variants_min_price');
                break;
            default:
                // Default: newest first
                $query->orderByDesc('created_at');
        }

        $perPage = $request->integer('per_page', 12);
        $products = $query->paginate($perPage);

        // Get categories for filtering
        $categories = Category::orderBy('name')->get(['id', 'name', 'slug']);

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'category_id' => $request->input('category_id'),
                'q' => $request->input('q'),
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Get featured/recommended products
     */
    public function featured(Request $request)
    {
        $products = Product::with(['images' => function ($q) {
            $q->where('is_primary', true);
        }, 'variants.inventories'])
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit($request->integer('limit', 8))
            ->get();

        return response()->json([
            'products' => $products,
        ]);
    }
}
