<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['images' => function ($q) {
            $q->orderByDesc('is_primary');
        }, 'variants.inventories'])
            ->whereIn('status', ['active', 'published']);

        // Filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where('name', 'like', "%{$q}%");
        }

        // Sort
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'price_low':
                // sort by min variant price
                $query->withMin('variants', 'price')->orderBy('variants_min_price');
                break;
            case 'price_high':
                $query->withMin('variants', 'price')->orderByDesc('variants_min_price');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get(['id','name']);

        return view('enduser.home', compact('products','categories','sort'));
    }
}
