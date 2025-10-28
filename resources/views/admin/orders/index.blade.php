@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-cart-check"></i> Quản lý đơn hàng</h1>
        <p class="text-muted mb-0">Quản lý đơn hàng và vận chuyển</p>
    </div>
</div>

<!-- Order Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-clock"></i>
            </div>
            <h5>Đang chờ</h5>
            <div class="value">{{ $orders->where('status', 'pending')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-info">
                <i class="bi bi-gear"></i>
            </div>
            <h5>Đang xử lý</h5>
            <div class="value">{{ $orders->where('status', 'processing')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="bi bi-truck"></i>
            </div>
            <h5>Đã giao</h5>
            <div class="value">{{ $orders->where('status', 'shipped')->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <h5>Đã nhận</h5>
            <div class="value">{{ $orders->where('status', 'delivered')->count() }}</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Mã đơn hàng, tên khách hàng..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Đang chờ</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                    <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Đã giao</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Đã nhận</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list"></i> Danh sách đơn hàng ({{ $orders->total() }} tổng)</span>
        <a href="{{ route('admin.orders.export') }}" class="btn btn-sm btn-success">
            <i class="bi bi-download"></i> Xuất
        </a>
    </div>
    <div class="card-body">
        @if($orders->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Mặt hàng</th>
                        <th>Tổng cộng</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Ngày</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td><strong>#{{ $order->id }}</strong></td>
                        <td>
                            <strong>{{ $order->user->full_name }}</strong>
                            <br>
                            <small class="text-muted">{{ $order->user->email }}</small>
                        </td>
                        <td>{{ $order->orderDetails->count() }} mặt hàng</td>
                        <td><strong>${{ number_format($order->payment->amount ?? 0, 2) }}</strong></td>
                        <td>
                            @if($order->status == 'pending')
                                <span class="badge bg-warning">Đang chờ</span>
                            @elseif($order->status == 'processing')
                                <span class="badge bg-info">Đang xử lý</span>
                            @elseif($order->status == 'shipped')
                                <span class="badge bg-primary">Đã giao</span>
                            @elseif($order->status == 'delivered')
                                <span class="badge bg-success">Đã nhận</span>
                            @else
                                <span class="badge bg-danger">{{ ucfirst($order->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->payment)
                                @if($order->payment->status == 'completed')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                @elseif($order->payment->status == 'pending')
                                    <span class="badge bg-warning">Đang chờ</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($order->payment->status) }}</span>
                                @endif
                            @else
                                <span class="badge bg-secondary">Chưa thanh toán</span>
                            @endif
                        </td>
                        <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.orders.show', $order->id) }}"
                                   class="btn btn-info" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($order->status != 'delivered' && $order->status != 'cancelled')
                                <button type="button" class="btn btn-primary" title="Cập nhật trạng thái"
                                        data-bs-toggle="modal" data-bs-target="#statusModal{{ $order->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @endif
                            </div>

                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal{{ $order->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cập nhật trạng thái đơn hàng</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.orders.status.update', $order->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Trạng thái mới</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Đang chờ</option>
                                                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                                                        <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Đã giao</option>
                                                        <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Đã nhận</option>
                                                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                <button type="submit" class="btn btn-primary">Cập nhật trạng thái</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $orders->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-cart-x" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy đơn hàng</p>
        </div>
        @endif
    </div>
</div>
@endsection
