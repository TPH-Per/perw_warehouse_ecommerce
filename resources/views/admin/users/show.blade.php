@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>User Details</h4>
                    <div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit User</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Personal Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Name:</th>
                                    <td>{{ $user->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>{{ $user->phone_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Role:</th>
                                    <td>{{ $user->role->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'secondary' : 'danger') }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Member Since:</th>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Statistics</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Total Orders:</th>
                                    <td>{{ $stats['total_orders'] }}</td>
                                </tr>
                                <tr>
                                    <th>Completed Orders:</th>
                                    <td>{{ $stats['completed_orders'] }}</td>
                                </tr>
                                <tr>
                                    <th>Total Spent:</th>
                                    <td>{{ number_format($stats['total_spent'], 0, ',', '.') }} VND</td>
                                </tr>
                                <tr>
                                    <th>Average Order Value:</th>
                                    <td>{{ number_format($stats['average_order_value'], 0, ',', '.') }} VND</td>
                                </tr>
                                <tr>
                                    <th>Total Reviews:</th>
                                    <td>{{ $stats['total_reviews'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Recent Orders</h5>
                            @if($user->orders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Order Code</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Total Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->orders->take(5) as $order)
                                        <tr>
                                            <td>{{ $order->order_code }}</td>
                                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'processing' ? 'info' : ($order->status === 'shipped' ? 'primary' : 'success')) }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($order->total_amount, 0, ',', '.') }} VND</td>
                                            <td>
                                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p>No orders found for this user.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            @if($user->status === 'active')
                            <form action="{{ route('admin.users.suspend', $user) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to suspend this user?')">Suspend User</button>
                            </form>
                            @else
                            <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to activate this user?')">Activate User</button>
                            </form>
                            @endif
                        </div>
                        <div>
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">Delete User</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.password.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <p><strong>{{ $user->full_name }} ({{ $user->email }})</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
