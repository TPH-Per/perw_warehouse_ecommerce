<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Cart;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 for pagination
        Paginator::defaultView('vendor.pagination.bootstrap-5');
        Paginator::defaultSimpleView('vendor.pagination.simple-bootstrap-5');

        // Share mini cart count for endUser navbar (lightweight query)
        View::composer('*', function ($view) {
            $count = 0;
            if (Auth::check()) {
                $cart = Cart::where('user_id', Auth::id())->first();
                if ($cart) {
                    // Avoid loading relations; sum quantity from details
                    try {
                        $count = (int) \DB::table('cart_details')->where('cart_id', $cart->id)->sum('quantity');
                    } catch (\Throwable $e) {
                        $count = 0;
                    }
                }
            }
            $view->with('enduserCartCount', $count);
        });
    }
}
