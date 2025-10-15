<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please log in.',
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Please log in to access the admin panel.');
        }

        // Check if user has admin role
        if (!auth()->user()->role || auth()->user()->role->name !== 'Admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            abort(403, 'Unauthorized. Admin access required.');
        }

        // Check if user account is active
        if (auth()->user()->status !== 'active') {
            auth()->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active. Please contact support.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Your account is not active. Please contact support.');
        }

        return $next($request);
    }
}
