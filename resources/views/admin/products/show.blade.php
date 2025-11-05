@extends('layouts.admin')

@section('title', 'Chi tiết sản phẩm')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> Chi tiết sản phẩm</h1>
        <p class="text-muted mb-0">Đang xem sản phẩm #{{ $product->id }}</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Chỉnh sửa
        </a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Product Information -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Thông tin sản phẩm
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="200">Tên sản phẩm:</th>
                        <td><strong>{{ $product->name }}</strong></td>
                    </tr>
                    <tr>
                        <th>Mô tả:</th>
                        <td>{{ $product->description ?? 'Không có mô tả' }}</td>
                    </tr>
                    <tr>
                        <th>Danh mục:</th>
                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Nhà cung cấp:</th>
                        <td>{{ $product->supplier->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Trạng thái:</th>
                        <td>
                            @if($product->status == 'draft')
                                <span class="badge bg-secondary">Bản nháp</span>
                            @elseif($product->status == 'published')
                                <span class="badge bg-success">Đã xuất bản</span>
                            @elseif($product->status == 'archived')
                                <span class="badge bg-danger">Đã lưu trữ</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($product->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Ngày tạo:</th>
                        <td>{{ $product->created_at->format('d M, Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Cập nhật lần cuối:</th>
                        <td>{{ $product->updated_at->format('d M, Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Product Variants -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tags"></i> Các mẫu mã sản phẩm ({{ $product->variants->count() }})</span>
            </div>
            <div class="card-body">
                @if($product->variants->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã sản phẩm</th>
                                <th>Tên mẫu mã</th>
                                <th>Giá</th>
                                <th>Trọng lượng</th>
                                <th>Kích thước</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product->variants as $variant)
                            <tr>
                                <td><code>{{ $variant->sku }}</code></td>
                                <td>{{ $variant->variant_name }}</td>
                                <td><strong>₫{{ number_format($variant->price, 2) }}</strong></td>
                                <td>{{ $variant->weight ? $variant->weight . ' kg' : 'N/A' }}</td>
                                <td>{{ $variant->dimensions ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-3">No variants available</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Product Images -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-images"></i> Hình ảnh sản phẩm
            </div>
            <div class="card-body">
                @if($product->images->count() > 0)
                    @foreach($product->images as $image)
                    <div class="mb-3">
                        <!-- Fix image URL handling -->
                        @if(Str::startsWith($image->image_url, ['http://', 'https://']))
                            <img src="{{ $image->image_url }}" class="img-fluid rounded" alt="Product Image">
                        @else
                            <img src="{{ asset(ltrim($image->image_url, '/')) }}" class="img-fluid rounded" alt="Product Image">
                        @endif
                        @if($image->is_primary)
                            <span class="badge bg-primary mt-2">Primary Image</span>
                        @endif
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-image" style="font-size: 3em; color: #ccc;"></i>
                        <p class="text-muted mt-2">Chưa có hình ảnh nào được tải lên</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Thống kê nhanh
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Tổng số mẫu mã</small>
                    <div><strong>{{ $product->variants->count() }}</strong></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Hình ảnh</small>
                    <div><strong>{{ $product->images->count() }}</strong></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Phạm vi giá</small>
                    <div>
                        @if($product->variants->count() > 0)
                            <strong>₫{{ number_format($product->variants->min('price'), 2) }}
                            - ₫{{ number_format($product->variants->max('price'), 2) }}</strong>
                        @else
                            <strong>N/A</strong>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
