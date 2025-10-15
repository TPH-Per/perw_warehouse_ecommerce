<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileApiController extends Controller
{
    /**
     * Get user profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'status' => $user->status,
            'role' => [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ],
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'full_name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|nullable|string|max:20',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
            ],
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Get user addresses
     */
    public function getAddresses(Request $request)
    {
        $user = $request->user();
        $addresses = Address::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($addresses);
    }

    /**
     * Get a single address
     */
    public function getAddress(Request $request, $id)
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($address);
    }

    /**
     * Create new address
     */
    public function createAddress(Request $request)
    {
        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            Address::where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return response()->json($address, 201);
    }

    /**
     * Update address
     */
    public function updateAddress(Request $request, $id)
    {
        $validated = $request->validate([
            'recipient_name' => 'sometimes|string|max:255',
            'recipient_phone' => 'sometimes|string|max:20',
            'address_line_1' => 'sometimes|string',
            'address_line_2' => 'nullable|string',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'country' => 'sometimes|string|max:100',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();
        $address = Address::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // If this is set as default, unset other defaults
        if (($validated['is_default'] ?? false) && !$address->is_default) {
            Address::where('user_id', $user->id)
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json($address);
    }

    /**
     * Delete address
     */
    public function deleteAddress(Request $request, $id)
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully'
        ]);
    }

    /**
     * Set default address
     */
    public function setDefaultAddress(Request $request, $id)
    {
        $user = $request->user();
        $address = Address::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // Unset other defaults
        Address::where('user_id', $user->id)
            ->where('id', '!=', $id)
            ->update(['is_default' => false]);

        // Set this as default
        $address->is_default = true;
        $address->save();

        return response()->json($address);
    }

    /**
     * Get order history
     */
    public function getOrderHistory(Request $request)
    {
        $user = $request->user();

        $orders = PurchaseOrder::where('user_id', $user->id)
            ->with([
                'orderDetails.variant.product.images',
                'payment.paymentMethod',
                'shipment'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($orders);
    }
}
