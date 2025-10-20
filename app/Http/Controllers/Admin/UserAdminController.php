<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserAdminController extends AdminController
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        // Debug logging
        Log::info('UserAdminController@index called', [
            'all_params' => $request->all(),
            'has_role_id' => $request->has('role_id'),
            'role_id_value' => $request->role_id,
            'role_id_type' => gettype($request->role_id),
        ]);

        $query = User::with(['role', 'orders']);

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role_id') && $request->role_id != '') {
            Log::info('Applying role filter', ['role_id' => $request->role_id]);
            $query->where('role_id', $request->role_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $users = $query->paginate(20);
        $roles = Role::all();

        Log::info('Users query result', ['total_users' => $users->total()]);

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            $user = User::create([
                'name' => $request->full_name,
                'role_id' => $request->role_id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.users.index')->with('success', 'User created successfully!');
        } catch (\Exception $e) {
            return $this->errorRedirect('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['role', 'addresses', 'orders.orderDetails', 'reviews']);

        // Calculate user statistics
        $stats = [
            'total_orders' => $user->orders()->count(),
            'completed_orders' => $user->orders()->where('status', 'delivered')->count(),
            'total_spent' => $user->orders()
                ->whereHas('payment', function($q) {
                    $q->where('status', 'completed');
                })
                ->join('payments', 'purchase_orders.id', '=', 'payments.order_id')
                ->sum('payments.amount'),
            'average_order_value' => $user->orders()
                ->whereHas('payment', function($q) {
                    $q->where('status', 'completed');
                })
                ->join('payments', 'purchase_orders.id', '=', 'payments.order_id')
                ->avg('payments.amount'),
            'total_reviews' => $user->reviews()->count(),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'full_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            $user->update([
                'name' => $request->full_name,
                'role_id' => $request->role_id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'status' => $request->status,
            ]);

            return redirect()->route('admin.users.show', $user->id)->with('success', 'User updated successfully!');
        } catch (\Exception $e) {
            return $this->errorRedirect('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return redirect()->route('admin.users.show', $user->id)->with('success', 'Password updated successfully!');
        } catch (\Exception $e) {
            return $this->errorRedirect('Failed to update password: ' . $e->getMessage());
        }
    }

    /**
     * Suspend user account
     */
    public function suspend(User $user)
    {
        try {
            $user->update(['status' => 'suspended']);

            return redirect()->route('admin.users.index')->with('success', 'User suspended successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to suspend user: ' . $e->getMessage());
        }
    }

    /**
     * Activate user account
     */
    public function activate(User $user)
    {
        try {
            $user->update(['status' => 'active']);

            return redirect()->route('admin.users.index')->with('success', 'User activated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to activate user: ' . $e->getMessage());
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'admins' => User::where('role_id', 1)->count(),
            'end_users' => User::where('role_id', 2)->count(),
        ];

        return $this->successResponse('Statistics retrieved successfully', $stats);
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request)
    {
        $query = User::with(['role']);

        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Created At']);

            // Add data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->full_name,
                    $user->email,
                    $user->phone_number,
                    $user->role->name,
                    $user->status,
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            // Check if user has any related orders
            $orderCount = $user->orders()->count();

            if ($orderCount > 0) {
                return redirect()->back()->with('error', 'Cannot delete user because they have ' . $orderCount . ' order(s). Please handle these orders first.');
            }

            // Check if user has any addresses
            $addressCount = $user->addresses()->count();

            if ($addressCount > 0) {
                return redirect()->back()->with('error', 'Cannot delete user because they have ' . $addressCount . ' address(es). Please handle these addresses first.');
            }

            // Check if user has any reviews
            $reviewCount = $user->reviews()->count();

            if ($reviewCount > 0) {
                return redirect()->back()->with('error', 'Cannot delete user because they have ' . $reviewCount . ' review(s). Please handle these reviews first.');
            }

            // If no related records, proceed with deletion
            $user->delete();

            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
