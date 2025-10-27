@extends('layouts.manager')

@section('title', 'Danh mục sản phẩm')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-box-seam"></i> Danh mục sản phẩm (Chỉ xem)</h1>
    <p class="text-muted mb-0">Duyệt thông tin sản phẩm</p>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.products.index') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Tên sản phẩm, mã sản phẩm, hoặc mô tả..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Bản nháp</option>
                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Đã lưu trữ</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Tìm kiếm
                </button>
                <a href="{{ route('manager.products.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Info Notice -->
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> <strong>Lưu ý:</strong> Bạn có thể xem chi tiết sản phẩm nhưng không thể tạo, chỉnh sửa hoặc xóa sản phẩm. Liên hệ quản trị viên để thực hiện các thay đổi sản phẩm.
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        @if(isset($products) && $products->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Mẫu mã</th>
                        <th>Phạm vi giá</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            <br>
                            <small class="text-muted">{{ Str::limit($product->description, 60) }}</small>
                        </td>
                        <td>
                            @if($product->category)
                                <span class="badge bg-secondary">{{ $product->category->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $product->variants->count() }} mẫu mã</span>
                        </td>
                        <td>
                            @if($product->variants->count() > 0)
                                ₫{{ number_format($product->variants->min('price'), 2) }}
                                @if($product->variants->min('price') != $product->variants->max('price'))
                                    - ₫{{ number_format($product->variants->max('price'), 2) }}
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($product->status == 'published')
                                <span class="badge bg-success">Đã xuất bản</span>
                            @elseif($product->status == 'draft')
                                <span class="badge bg-secondary">Bản nháp</span>
                            @elseif($product->status == 'archived')
                                <span class="badge bg-danger">Đã lưu trữ</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($product->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('manager.products.show', $product->id) }}"
                               class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> Xem chi tiết
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy sản phẩm nào</p>
        </div>
        @endif
    </div>
</div>
@endsection
