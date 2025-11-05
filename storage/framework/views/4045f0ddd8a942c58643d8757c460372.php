<?php $__env->startSection('title', 'Chỉnh sửa sản phẩm'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1><i class="bi bi-pencil"></i> Chỉnh sửa sản phẩm</h1>
    <p class="text-muted mb-0">Cập nhật thông tin sản phẩm</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Thông tin sản phẩm
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('admin.products.update', $product)); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo e(old('name', $product->name)); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="4"><?php echo e(old('description', $product->description)); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Danh mục <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Chọn danh mục</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category->id); ?>"
                                            <?php echo e(old('category_id', $product->category_id) == $category->id ? 'selected' : ''); ?>>
                                        <?php echo e($category->parent_id ? '— ' : ''); ?><?php echo e($category->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="supplier_id" class="form-label">Nhà cung cấp <span class="text-danger">*</span></label>
                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                <option value="">Chọn nhà cung cấp</option>
                                <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($supplier->id); ?>"
                                            <?php echo e(old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : ''); ?>>
                                        <?php echo e($supplier->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" <?php echo e(old('status', $product->status) == 'draft' ? 'selected' : ''); ?>>Bản nháp</option>
                            <option value="published" <?php echo e(old('status', $product->status) == 'published' ? 'selected' : ''); ?>>Đã xuất bản</option>
                            <option value="archived" <?php echo e(old('status', $product->status) == 'archived' ? 'selected' : ''); ?>>Đã lưu trữ</option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <h5><i class="bi bi-tags"></i> Các mẫu mã sản phẩm</h5>
                    <p class="text-muted">Sản phẩm này có <?php echo e($product->variants->count()); ?> mẫu mã. Bạn có thể quản lý các mẫu mã từ trang chi tiết sản phẩm.</p>

                    <div class="table-responsive mb-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Mã sản phẩm</th>
                                    <th>Tên mẫu mã</th>
                                    <th>Giá</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $product->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><code><?php echo e($variant->sku); ?></code></td>
                                    <td><?php echo e($variant->variant_name); ?></td>
                                    <td>₫<?php echo e(number_format($variant->price, 2)); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-4">

                    <h5><i class="bi bi-images"></i> Hình ảnh sản phẩm</h5>
                    <p class="text-muted">Sản phẩm này có <?php echo e($product->images->count()); ?> hình ảnh. Bạn có thể quản lý hình ảnh từ trang chi tiết sản phẩm.</p>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Cập nhật sản phẩm
                        </button>
                        <a href="<?php echo e(route('admin.products.show', $product)); ?>" class="btn btn-secondary">
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
                <h6>Chỉnh sửa sản phẩm</h6>
                <ul class="small">
                    <li>Điền vào tất cả các trường bắt buộc được đánh dấu <span class="text-danger">*</span></li>
                    <li>Các mẫu mã và hình ảnh sản phẩm được quản lý riêng biệt</li>
                    <li>Để thêm/xóa mẫu mã, sử dụng trang chi tiết sản phẩm</li>
                    <li>Để tải lên/xóa hình ảnh, sử dụng trang chi tiết sản phẩm</li>
                    <li>Mã sản phẩm phải là duy nhất trong tất cả các mẫu mã</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/products/edit.blade.php ENDPATH**/ ?>