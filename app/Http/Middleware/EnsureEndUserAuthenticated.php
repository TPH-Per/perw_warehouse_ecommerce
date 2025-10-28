<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEndUserAuthenticated
{
    /**
     * Handle an incoming request.
     * If not authenticated, redirect to end-user login route with intended URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            // Preserve intended URL then redirect to end-user login
            session(['url.intended' => $request->fullUrl()]);
            return redirect()->route('enduser.login');
        }
        return $next($request);
    }
}

