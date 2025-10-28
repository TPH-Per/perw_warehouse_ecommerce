<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show($id)
    {
        $product = Product::with(['images' => function ($q) {
            $q->orderByDesc('is_primary');
        }, 'variants.inventories', 'supplier', 'category'])
            ->findOrFail($id);

        return view('enduser.product', compact('product'));
    }
}

