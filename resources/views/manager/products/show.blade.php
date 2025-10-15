@extends('layouts.manager')

@section('title', 'Chi tiết sản phẩm')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> {{ $product->name }}</h1>
        <p class="text-muted mb-0">Xem thông tin sản phẩm</p>
    </div>
    <a href="{{ route('manager.products.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại danh sách sản phẩm
    </a>
</div>

<!-- Info Notice -->
<div class="alert alert-warning">
    <i class="bi bi-lock"></i> <strong>Chế độ chỉ đọc:</strong> Bạn không thể chỉnh sửa thông tin sản phẩm. Liên hệ quản trị viên để thực hiện thay đổi.
</div>

<div class="row">
    <!-- Product Information -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-info-circle"></i> Thông tin sản phẩm
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Tên sản phẩm:</strong><br>
                        {{ $product->name }}
                    </div>
                    <div class="col-md-6">
                        <strong>Danh mục:</strong><br>
                        {{ $product->category->name ?? 'N/A' }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <strong>Mô tả:</strong><br>
                        {{ $product->description ?? 'Không có mô tả' }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Trạng thái:</strong><br>
                        @if($product->status == 'active')
                            <span class="badge bg-success">Hoạt động</span>
                        @else
                            <span class="badge bg-secondary">Không hoạt động</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Ngày tạo:</strong><br>
                        {{ $product->created_at->format('d M, Y') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Variants -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <i class="bi bi-boxes"></i> Các mẫu mã sản phẩm
            </div>
            <div class="card-body">
                @if($product->variants->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tên mẫu mã</th>
                                <th>Mã sản phẩm</th>
                                <th>Giá</th>
                                <th>Giá gốc</th>
                                <th>Tổng tồn kho</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                            <tr>
                                <td><strong>{{ $variant->name }}</strong></td>
                                <td>{{ $variant->sku }}</td>
                                <td><strong class="text-success">₫{{ number_format($variant->price, 2) }}</strong></td>
                                <td>
                                    @if($variant->original_price)
                                        <span class="text-muted text-decoration-line-through">
                                            ₫{{ number_format($variant->original_price, 2) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $totalStock = $variant->inventories->sum('quantity_on_hand');
                                    @endphp
                                    <span class="badge {{ $totalStock > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $totalStock }} đơn vị
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-3">Không có mẫu mã nào</p>
                @endif
            </div>
        </div>

        <!-- Inventory by Warehouse -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-building"></i> Kho hàng theo từng kho
            </div>
            <div class="card-body">
                @if($product->variants->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mẫu mã</th>
                                <th>Kho hàng</th>
                                <th>Hiện có</th>
                                <th>Đã đặt</th>
                                <th>Có sẵn</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                                @foreach($variant->inventories as $inventory)
                                <tr>
                                    <td>{{ $variant->name }}</td>
                                    <td>{{ $inventory->warehouse->name }}</td>
                                    <td>{{ $inventory->quantity_on_hand }}</td>
                                    <td>{{ $inventory->quantity_reserved }}</td>
                                    <td>
                                        <strong class="text-success">
                                            {{ $inventory->quantity_on_hand - $inventory->quantity_reserved }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($inventory->quantity_on_hand <= 0)
                                            <span class="badge bg-danger">Hết hàng</span>
                                        @elseif($inventory->quantity_on_hand <= $inventory->reorder_level)
                                            <span class="badge bg-warning">Hết hàng</span>
                                        @else
                                            <span class="badge bg-success">Còn hàng</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-3">Không có thông tin kho hàng</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Product Images -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-images"></i> Hình ảnh sản phẩm
            </div>
            <div class="card-body">
                @if($product->images && $product->images->count() > 0)
                    @foreach($product->images as $image)
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $image->image_path) }}"
                                 alt="{{ $product->name }}"
                                 class="img-fluid rounded"
                                 style="width: 100%;">
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-image" style="font-size: 3rem; color: var(--light-sky);"></i>
                        <p class="text-muted mt-2">Không có hình ảnh</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
