@extends('layouts.admin')

@section('title', 'Quản lý sản phẩm')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> Quản lý sản phẩm</h1>
        <p class="text-muted mb-0">Quản lý danh mục sản phẩm của bạn</p>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Thêm sản phẩm mới
    </a>
</div>

<!-- Quick Add Category -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-tags"></i> Thêm danh mục sản phẩm
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.categories.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Tên danh mục *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="VD: Figure, Nendoroid..." value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle"></i> Thêm danh mục
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Add Supplier -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-truck"></i> Thêm nhà cung cấp
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.suppliers.store') }}" class="row g-3">
            @csrf
            <div class="col-md-5">
                <label class="form-label">Tên nhà cung cấp *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="VD: Good Smile Company" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-5">
                <label class="form-label">Thông tin liên hệ (tuỳ chọn)</label>
                <input type="text" name="contact_info" class="form-control" placeholder="Email/SĐT/Địa chỉ" value="{{ old('contact_info') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle"></i> Thêm NCC
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tên sản phẩm..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nhà cung cấp</label>
                <select name="supplier_id" class="form-select">
                    <option value="">Tất cả nhà cung cấp</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}"
                                {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Bản nháp</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                    <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Đã lưu trữ</option>
                </select>
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

<!-- Products Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list"></i> Danh sách sản phẩm ({{ $products->total() }} tổng cộng)</span>
    </div>
    <div class="card-body">
        @if($products->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Danh mục</th>
                        <th>Nhà cung cấp</th>
                        <th>Mẫu mã</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td><strong>#{{ $product->id }}</strong></td>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            <br>
                            <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                        </td>
                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                        <td>{{ $product->supplier->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $product->variants->count() }} mẫu mã</span>
                        </td>
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
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.products.show', $product->id) }}"
                                   class="btn btn-info" title="Xem">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product->id) }}"
                                   class="btn btn-warning" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product->id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy sản phẩm nào</p>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tạo sản phẩm đầu tiên
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
