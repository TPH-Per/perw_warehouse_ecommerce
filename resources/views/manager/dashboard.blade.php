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
                    <a href="{{ route('manager.inventory.transactions') }}" class="btn btn-success btn-lg">
                        <i class="bi bi-arrow-repeat"></i> Xuất Nhập Kho
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
                    <li>✓ Xuất nhập kho hàng</li>
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
                    <li>Sử dụng tính năng "Xuất Nhập Kho" để xem lịch sử giao dịch</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
