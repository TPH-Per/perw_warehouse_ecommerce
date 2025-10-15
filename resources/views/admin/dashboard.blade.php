@extends('layouts.admin')

@section('title', 'Bảng điều khiển')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-speedometer2"></i> Tổng quan Bảng điều khiển</h1>
    <p class="text-muted mb-0">Chào mừng trở lại, {{ auth()->user()->full_name }}!</p>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <h5>Tổng số Người dùng</h5>
            <div class="value">{{ number_format($stats['total_users']) }}</div>
            <small class="text-muted">
                <i class="bi bi-arrow-up text-success"></i>
                {{ $stats['new_users_this_month'] }} mới trong tháng này
            </small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-box-seam-fill"></i>
            </div>
            <h5>Tổng số Sản phẩm</h5>
            <div class="value">{{ number_format($stats['total_products']) }}</div>
            <small class="text-muted">
                {{ $stats['active_products'] }} sản phẩm đang hoạt động
            </small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-cart-check-fill"></i>
            </div>
            <h5>Tổng số Đơn hàng</h5>
            <div class="value">{{ number_format($stats['total_orders']) }}</div>
            <small class="text-muted">
                <span class="badge bg-warning">{{ $stats['pending_orders'] }} đang chờ</span>
                <span class="badge bg-info">{{ $stats['processing_orders'] }} đang xử lý</span>
            </small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <h5>Tổng Doanh thu</h5>
            <div class="value">${{ number_format($stats['total_revenue'], 2) }}</div>
            <small class="text-muted">
                ${{ number_format($stats['revenue_this_month'], 2) }} trong tháng này
            </small>
        </div>
    </div>
</div>

<!-- Additional Stats Row -->
<div class="row mt-3">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <h5>Mặt hàng gần hết</h5>
            <div class="value text-danger">{{ $stats['low_stock_items'] }}</div>
            <small class="text-muted">{{ $stats['out_of_stock_items'] }} hết hàng</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-star-fill"></i>
            </div>
            <h5>Đánh giá đang chờ</h5>
            <div class="value text-warning">{{ $stats['pending_reviews'] }}</div>
            <small class="text-muted">Đang chờ phê duyệt</small>
        </div>
    </div>

    <div class="col-xl-6 col-md-12">
        <div class="stat-card">
            <h5><i class="bi bi-graph-up"></i> Hành động nhanh</h5>
            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Thêm Sản phẩm
                </a>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-list-check"></i> Xem Đơn hàng
                </a>
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-box"></i> Kiểm tra Kho hàng
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Đơn hàng gần đây
            </div>
            <div class="card-body">
                @if($recentOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID Đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Trạng thái</th>
                                <th>Tổng cộng</th>
                                <th>Ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                            <tr>
                                <td><strong>#{{ $order->id }}</strong></td>
                                <td>{{ $order->user->full_name }}</td>
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
                                <td>${{ number_format($order->payment->amount ?? 0, 2) }}</td>
                                <td>{{ $order->created_at->format('d M, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">Chưa có đơn hàng nào</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Phân bố Trạng thái Đơn hàng
            </div>
            <div class="card-body">
                @foreach($orderStatusDistribution as $status => $count)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-capitalize">{{ $status }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $stats['total_orders'] > 0 ? ($count / $stats['total_orders'] * 100) : 0 }}%">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-person-plus"></i> Người dùng gần đây
            </div>
            <div class="card-body">
                @foreach($recentUsers as $user)
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong>{{ $user->full_name }}</strong>
                        <br>
                        <small class="text-muted">{{ $user->email }}</small>
                    </div>
                    <span class="badge bg-primary">{{ $user->role->name }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert -->
@if($lowStockItems->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill"></i> Cảnh báo Hàng gần hết
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Mã sản phẩm</th>
                                <th>Kho hàng</th>
                                <th>Hiện có</th>
                                <th>Mức đặt hàng lại</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                            <tr>
                                <td>{{ $item->productVariant->product->name }}</td>
                                <td>{{ $item->productVariant->sku }}</td>
                                <td>{{ $item->warehouse->name }}</td>
                                <td><span class="badge bg-danger">{{ $item->quantity_on_hand }}</span></td>
                                <td>{{ $item->reorder_level }}</td>
                                <td>
                                    <a href="{{ route('admin.inventory.show', $item->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Xem
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
