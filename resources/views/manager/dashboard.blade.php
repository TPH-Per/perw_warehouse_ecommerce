@extends('layouts.manager')

@section('title', 'Bảng điều khiển Quản lý')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-speedometer2"></i> Bảng điều khiển Quản lý Kho</h1>
    <p class="text-muted mb-0">Chào mừng trở lại, {{ auth()->user()->full_name }}!</p>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-boxes"></i>
            </div>
            <h5>Tổng kho hàng</h5>
            <div class="value">{{ number_format($stats['total_inventory_items']) }}</div>
            <small class="text-muted">sản phẩm trong kho</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h5>Hết hàng</h5>
            <div class="value text-warning">{{ $stats['low_stock_items'] }}</div>
            <small class="text-muted">{{ $stats['out_of_stock_items'] }} hết hàng</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-info">
                <i class="bi bi-box-seam"></i>
            </div>
            <h5>Tổng sản phẩm</h5>
            <div class="value">{{ number_format($stats['total_products']) }}</div>
            <small class="text-muted">trong danh mục</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-cart-check"></i>
            </div>
            <h5>Bán hàng hôm nay</h5>
            <div class="value">{{ $stats['today_sales'] }}</div>
            <small class="text-muted">bán hàng trực tiếp</small>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-lightning-charge"></i> Hành động nhanh
            </div>
            <div class="card-body">
                <div class="d-flex gap-3">
                    <a href="{{ route('manager.sales.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle"></i> Tạo đơn bán hàng mới
                    </a>
                    <a href="{{ route('manager.inventory.index') }}" class="btn btn-secondary btn-lg">
                        <i class="bi bi-boxes"></i> Quản lý kho hàng
                    </a>
                    <a href="{{ route('manager.inventory.low-stock') }}" class="btn btn-warning btn-lg">
                        <i class="bi bi-exclamation-triangle"></i> Xem hàng sắp hết
                    </a>
                    <a href="{{ route('manager.products.index') }}" class="btn btn-info btn-lg">
                        <i class="bi bi-search"></i> Duyệt sản phẩm
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row mt-4">
    <!-- Low Stock Alerts -->
    <div class="col-lg-6">
        <div class="card border-warning">
            <div class="card-header bg-warning text-white">
                <i class="bi bi-exclamation-triangle-fill"></i> Cảnh báo hàng sắp hết
            </div>
            <div class="card-body">
                @if($lowStockItems->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Kho</th>
                                <th>Số lượng</th>
                                <th>Đặt hàng lại</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->productVariant->product->name }}</strong>
                                    <br>
                                    <small>{{ $item->productVariant->sku }}</small>
                                </td>
                                <td>{{ $item->warehouse->name }}</td>
                                <td><span class="badge bg-danger">{{ $item->quantity_on_hand }}</span></td>
                                <td>{{ $item->reorder_level }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('manager.inventory.low-stock') }}" class="btn btn-sm btn-warning mt-2">
                    Xem tất cả hàng sắp hết
                </a>
                @else
                <p class="text-muted text-center py-3">Không có sản phẩm nào sắp hết hàng</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Today's Direct Sales -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-cart-check"></i> Bán hàng trực tiếp hôm nay
            </div>
            <div class="card-body">
                @if($recentSales->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Đơn hàng #</th>
                                <th>Khách hàng</th>
                                <th>Số tiền</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentSales as $sale)
                            <tr>
                                <td><strong>#{{ $sale->id }}</strong></td>
                                <td>{{ $sale->user->full_name ?? 'Khách lẻ' }}</td>
                                <td><strong>${{ number_format($sale->payment->amount ?? 0, 2) }}</strong></td>
                                <td>{{ $sale->created_at->format('H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('manager.sales.index') }}" class="btn btn-sm btn-success mt-2">
                    Xem tất cả đơn hàng
                </a>
                @else
                <p class="text-muted text-center py-3">Không có đơn hàng nào hôm nay</p>
                <a href="{{ route('manager.sales.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tạo đơn hàng đầu tiên
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Information Cards -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5><i class="bi bi-info-circle"></i> Quyền hạn của bạn</h5>
                <ul class="mb-0">
                    <li>✓ Quản lý số lượng kho hàng</li>
                    <li>✓ Tạo đơn bán hàng trực tiếp (khách hàng tại chỗ)</li>
                    <li>✓ Xem sản phẩm và giá cả</li>
                    <li>✓ Chuyển kho giữa các kho hàng</li>
                    <li>✗ Không thể thêm/xóa sản phẩm</li>
                    <li>✗ Không thể quản lý đơn hàng vận chuyển</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5><i class="bi bi-lightbulb"></i> Mẹo nhanh</h5>
                <ul class="mb-0">
                    <li>Luôn kiểm tra kho hàng trước khi hoàn thành đơn bán</li>
                    <li>Cập nhật số lượng kho ngay sau khi giao dịch</li>
                    <li>Báo cáo các mặt hàng sắp hết cho quản trị viên hệ thống</li>
                    <li>Đơn bán hàng trực tiếp chỉ dành cho khách hàng tại chỗ</li>
                    <li>Sử dụng tính năng điều chỉnh kho cho các điều chỉnh hàng tồn kho</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
