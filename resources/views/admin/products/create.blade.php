@extends('layouts.admin')

@section('title', 'Tạo sản phẩm')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-plus-circle"></i> Tạo sản phẩm mới</h1>
    <p class="text-muted mb-0">Thêm sản phẩm mới vào danh mục của bạn</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Thông tin sản phẩm
            </div>
            <div class="card-body">
                <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="4">{{ old('description') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Chọn danh mục</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="supplier_id" class="form-label">Nhà cung cấp <span class="text-danger">*</span></label>
                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                <option value="">Chọn nhà cung cấp</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                            {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Bản nháp</option>
                            <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Đã xuất bản</option>
                            <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Đã lưu trữ</option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <h5><i class="bi bi-tags"></i> Các mẫu mã sản phẩm</h5>
                    <p class="text-muted">Thêm ít nhất một mẫu mã cho sản phẩm này</p>

                    <div id="variantsContainer">
                        <div class="variant-item card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Mã sản phẩm <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="variants[0][sku]" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tên mẫu mã <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="variants[0][variant_name]" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Giá <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="variants[0][price]"
                                               step="0.01" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Trọng lượng (kg)</label>
                                        <input type="number" class="form-control" name="variants[0][weight]"
                                               step="0.01" min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kích thước (D x R x C)</label>
                                        <input type="text" class="form-control" name="variants[0][dimensions]"
                                               placeholder="ví dụ: 10 x 5 x 3 cm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary mb-3" id="addVariantBtn">
                        <i class="bi bi-plus"></i> Thêm mẫu mã khác
                    </button>

                    <hr class="my-4">

                    <h5><i class="bi bi-images"></i> Hình ảnh sản phẩm</h5>
                    <div class="mb-3">
                        <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                        <small class="text-muted">Bạn có thể tải lên nhiều hình ảnh. Hình ảnh đầu tiên sẽ là hình ảnh chính.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Tạo sản phẩm
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-lightbulb"></i> Mẹo
            </div>
            <div class="card-body">
                <h6>Tạo sản phẩm</h6>
                <ul class="small">
                    <li>Điền vào tất cả các trường bắt buộc được đánh dấu <span class="text-danger">*</span></li>
                    <li>Mỗi sản phẩm phải có ít nhất một mẫu mã</li>
                    <li>Mã sản phẩm phải là duy nhất trong tất cả các mẫu mã</li>
                    <li>Sử dụng tên rõ ràng, mô tả</li>
                    <li>Tải lên hình ảnh chất lượng cao để trình bày tốt hơn</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let variantIndex = 1;

document.getElementById('addVariantBtn').addEventListener('click', function() {
    const container = document.getElementById('variantsContainer');
    const newVariant = document.createElement('div');
    newVariant.className = 'variant-item card mb-3';
    newVariant.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <h6>Mẫu mã ${variantIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-danger remove-variant">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Mã sản phẩm <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="variants[${variantIndex}][sku]" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tên mẫu mã <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="variants[${variantIndex}][variant_name]" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Giá <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="variants[${variantIndex}][price]"
                           step="0.01" min="0" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Trọng lượng (kg)</label>
                    <input type="number" class="form-control" name="variants[${variantIndex}][weight]"
                           step="0.01" min="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Kích thước (D x R x C)</label>
                    <input type="text" class="form-control" name="variants[${variantIndex}][dimensions]"
                           placeholder="ví dụ: 10 x 5 x 3 cm">
                </div>
            </div>
        </div>
    `;
    container.appendChild(newVariant);
    variantIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-variant')) {
        if (document.querySelectorAll('.variant-item').length > 1) {
            e.target.closest('.variant-item').remove();
        } else {
            alert('Phải có ít nhất một mẫu mã');
        }
    }
});
</script>
@endpush
@endsection
