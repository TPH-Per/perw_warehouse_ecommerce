<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Return success JSON response
     */
    protected function successResponse(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return error JSON response
     */
    protected function errorResponse(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Return success redirect with message
     */
    protected function successRedirect(string $route, string $message, array $parameters = []): RedirectResponse
    {
        return redirect()->route($route, $parameters)->with('success', $message);
    }

    /**
     * Return error redirect with message
     */
    protected function errorRedirect(string $message): RedirectResponse
    {
        return back()->with('error', $message)->withInput();
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(): bool
    {
        return Auth::check() && Auth::user()->role->name === 'admin';
    }
}
