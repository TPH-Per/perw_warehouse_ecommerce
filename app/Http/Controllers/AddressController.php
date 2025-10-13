<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Get authenticated user ID
     */
    private function getAuthenticatedUserId(): int
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new \Exception('User not authenticated');
        }
        return $userId;
    }

    /**
     * Display user's addresses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $addresses = Address::where('user_id', $userId)
                               ->orderBy('is_default', 'desc')
                               ->orderBy('created_at', 'desc')
                               ->get();

            return response()->json([
                'addresses' => $addresses,
                'message' => 'Addresses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve addresses.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Display the specified address
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $address = Address::where('id', $id)
                             ->where('user_id', $userId)
                             ->firstOrFail();

            return response()->json([
                'address' => $address,
                'message' => 'Address retrieved successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Address not found.',
                'errors' => ['address' => ['The requested address does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve address.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Store a newly created address
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'recipient_name' => 'required|string|max:255',
                'recipient_phone' => 'required|string|max:20',
                'street_address' => 'required|string|max:255',
                'ward' => 'required|string|max:100',
                'district' => 'required|string|max:100',
                'city' => 'required|string|max:100',
                'is_default' => 'sometimes|boolean',
            ]);

            $userId = $this->getAuthenticatedUserId();
            $isDefault = $request->get('is_default', false);

            // If this is set as default, unset other default addresses
            if ($isDefault) {
                Address::where('user_id', $userId)
                       ->where('is_default', true)
                       ->update(['is_default' => false]);
            }

            // If this is the user's first address, make it default
            $addressCount = Address::where('user_id', $userId)->count();
            if ($addressCount === 0) {
                $isDefault = true;
            }

            $address = Address::create([
                'user_id' => $userId,
                'recipient_name' => $request->recipient_name,
                'recipient_phone' => $request->recipient_phone,
                'street_address' => $request->street_address,
                'ward' => $request->ward,
                'district' => $request->district,
                'city' => $request->city,
                'is_default' => $isDefault,
            ]);

            return response()->json([
                'address' => $address,
                'message' => 'Address created successfully.'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create address.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Update the specified address
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $address = Address::where('id', $id)
                             ->where('user_id', $userId)
                             ->firstOrFail();

            $request->validate([
                'recipient_name' => 'sometimes|string|max:255',
                'recipient_phone' => 'sometimes|string|max:20',
                'street_address' => 'sometimes|string|max:255',
                'ward' => 'sometimes|string|max:100',
                'district' => 'sometimes|string|max:100',
                'city' => 'sometimes|string|max:100',
                'is_default' => 'sometimes|boolean',
            ]);

            // If setting as default, unset other default addresses
            if ($request->has('is_default') && $request->is_default) {
                Address::where('user_id', $userId)
                       ->where('id', '!=', $id)
                       ->where('is_default', true)
                       ->update(['is_default' => false]);
            }

            $address->update($request->only([
                'recipient_name',
                'recipient_phone',
                'street_address',
                'ward',
                'district',
                'city',
                'is_default'
            ]));

            return response()->json([
                'address' => $address,
                'message' => 'Address updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Address not found.',
                'errors' => ['address' => ['The requested address does not exist or does not belong to you.']]
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update address.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Remove the specified address (Soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $address = Address::where('id', $id)
                             ->where('user_id', $userId)
                             ->firstOrFail();

            // If deleting default address, set another address as default
            if ($address->is_default) {
                $nextAddress = Address::where('user_id', $userId)
                                     ->where('id', '!=', $id)
                                     ->first();
                
                if ($nextAddress) {
                    $nextAddress->update(['is_default' => true]);
                }
            }

            $address->delete();

            return response()->json([
                'message' => 'Address deleted successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Address not found.',
                'errors' => ['address' => ['The requested address does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete address.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Set address as default
     */
    public function setDefault(int $id): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $address = Address::where('id', $id)
                             ->where('user_id', $userId)
                             ->firstOrFail();

            // Unset other default addresses
            Address::where('user_id', $userId)
                   ->where('id', '!=', $id)
                   ->update(['is_default' => false]);

            // Set this address as default
            $address->update(['is_default' => true]);

            return response()->json([
                'address' => $address,
                'message' => 'Default address updated successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Address not found.',
                'errors' => ['address' => ['The requested address does not exist or does not belong to you.']]
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to set default address.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }

    /**
     * Get user's default address
     */
    public function getDefault(Request $request): JsonResponse
    {
        try {
            $userId = $this->getAuthenticatedUserId();
            
            $defaultAddress = Address::where('user_id', $userId)
                                    ->where('is_default', true)
                                    ->first();

            if (!$defaultAddress) {
                return response()->json([
                    'message' => 'No default address found.',
                    'errors' => ['address' => ['You have not set a default address.']]
                ], 404);
            }

            return response()->json([
                'address' => $defaultAddress,
                'message' => 'Default address retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve default address.',
                'errors' => ['server' => ['An unexpected error occurred.']]
            ], 500);
        }
    }
}