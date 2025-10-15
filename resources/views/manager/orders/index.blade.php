@extends('layouts.manager')

@section('title', 'Đơn hàng vận chuyển')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-truck"></i> Đơn hàng vận chuyển</h1>
    <p class="text-muted mb-0">Quản lý đơn hàng vận chuyển của khách hàng</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.orders.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Mã đơn hàng hoặc khách hàng..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="status">
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
                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                <a href="{{ route('manager.orders.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        @if($orders->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Mặt hàng</th>
                        <th>Tổng cộng</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt hàng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td><strong>{{ $order->order_code }}</strong></td>
                        <td>
                            {{ $order->shipping_recipient_name }}
                            <br>
                            <small class="text-muted">{{ $order->shipping_recipient_phone }}</small>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $order->orderDetails->count() }} mặt hàng</span>
                        </td>
                        <td><strong class="text-success">₫{{ number_format($order->total_amount, 2) }}</strong></td>
                        <td>
                            @if($order->status == 'pending')
                                <span class="badge bg-warning">Đang chờ</span>
                            @elseif($order->status == 'processing')
                                <span class="badge bg-info">Đang xử lý</span>
                            @elseif($order->status == 'shipped')
                                <span class="badge bg-primary">Đã giao</span>
                            @elseif($order->status == 'delivered')
                                <span class="badge bg-success">Đã nhận</span>
                            @elseif($order->status == 'cancelled')
                                <span class="badge bg-danger">Đã hủy</span>
                            @endif
                        </td>
                        <td>{{ $order->created_at->format('d M, Y') }}</td>
                        <td>
                            <a href="{{ route('manager.orders.show', $order->id) }}"
                               class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $orders->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy đơn hàng vận chuyển nào</p>
        </div>
        @endif
    </div>
</div>
@endsection
