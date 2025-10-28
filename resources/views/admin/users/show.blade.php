@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Chi tiết người dùng</h4>
                    <div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Quay lại Người dùng</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Chỉnh sửa</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin cá nhân</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Tên:</th>
                                    <td>{{ $user->full_name }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td>{{ $user->phone_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Vai trò:</th>
                                    <td>{{ $user->role->name }}</td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td>
                                        <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'secondary' : 'danger') }}">
                                            {{ $user->status == 'active' ? 'Hoạt động' : ($user->status == 'inactive' ? 'Không hoạt động' : 'Tạm khóa') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Thành viên từ:</th>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Thống kê</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Tổng số đơn hàng:</th>
                                    <td>{{ $stats['total_orders'] }}</td>
                                </tr>
                                <tr>
                                    <th>Đơn hàng hoàn tất:</th>
                                    <td>{{ $stats['completed_orders'] }}</td>
                                </tr>
                                <tr>
                                    <th>Tổng chi tiêu:</th>
                                    <td>{{ number_format($stats['total_spent'], 0, ',', '.') }} VND</td>
                                </tr>
                                <tr>
                                    <th>Giá trị đơn hàng trung bình:</th>
                                    <td>{{ number_format($stats['average_order_value'], 0, ',', '.') }} VND</td>
                                </tr>
                                <tr>
                                    <th>Tổng số đánh giá:</th>
                                    <td>{{ $stats['total_reviews'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Đơn hàng gần đây</h5>
                            @if($user->orders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mã đơn hàng</th>
                                            <th>Ngày</th>
                                            <th>Trạng thái</th>
                                            <th>Tổng tiền</th>
                                            <th>Hành động</th>
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
                                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p>Không có đơn hàng nào cho người dùng này.</p>
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
                                @method('PUT')
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Bạn có chắc muốn tạm khóa người dùng này?')">Tạm khóa người dùng</button>
                            </form>
                            @else
                            <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success" onclick="return confirm('Bạn có chắc muốn kích hoạt người dùng này?')">Kích hoạt người dùng</button>
                            </form>
                            @endif
                        </div>
                        <div>
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Đổi mật khẩu</button>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">Xóa người dùng</button>
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
                <h5 class="modal-title" id="changePasswordModalLabel">Đổi mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('admin.users.password.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Cập nhật mật khẩu</button>
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
                <h5 class="modal-title" id="deleteUserModalLabel">Xóa người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc muốn xóa người dùng này? Hành động này không thể hoàn tác.</p>
                <p><strong>{{ $user->full_name }} ({{ $user->email }})</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa người dùng</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
