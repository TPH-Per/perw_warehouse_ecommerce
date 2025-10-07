<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kiểm tra xem người dùng đã đăng nhập chưa
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Kiểm tra xem người dùng có phải là Admin không (dựa vào role_id)
        // Giả định role_id 1 là Admin
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. You are not an Admin.'], 403);
        }

        return $next($request);
    }
}