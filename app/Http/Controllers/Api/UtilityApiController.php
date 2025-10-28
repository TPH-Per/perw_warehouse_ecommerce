<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;

class UtilityApiController extends Controller
{
    /**
     * Active payment methods (public)
     */
    public function paymentMethods()
    {
        $methods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($methods);
    }

    /**
     * Active shipping methods (public)
     */
    public function shippingMethods()
    {
        $methods = ShippingMethod::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cost']);

        return response()->json($methods);
    }
}

