<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryApiController extends Controller
{
    /**
     * List categories with product counts
     */
    public function index(Request $request)
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'parent_id' => $cat->parent_id,
                    'product_count' => $cat->products_count,
                ];
            });

        return response()->json($categories);
    }

    /**
     * List products by category
     */
    public function products(Request $request, $id)
    {
        $perPage = $request->input('per_page', 15);

        $query = Product::with(['category', 'supplier', 'variants', 'images'])
            ->withCount('images as images_count')
            ->where('status', 'published')
            ->where('category_id', $id)
            ->orderByDesc('images_count')
            ->orderByDesc('id');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

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

        $products = $query->paginate($perPage);
        return response()->json($products);
    }
}
