<?php $__env->startSection('title', 'Chi tiết sản phẩm'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> Chi tiết sản phẩm</h1>
        <p class="text-muted mb-0">Đang xem sản phẩm #<?php echo e($product->id); ?></p>
    </div>
    <div class="btn-group">
        <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Chỉnh sửa
        </a>
        <a href="<?php echo e(route('admin.products.index')); ?>" class="btn btn-secondary">
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
                        <td><strong><?php echo e($product->name); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Mô tả:</th>
                        <td><?php echo e($product->description ?? 'Không có mô tả'); ?></td>
                    </tr>
                    <tr>
                        <th>Danh mục:</th>
                        <td><?php echo e($product->category->name ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Nhà cung cấp:</th>
                        <td><?php echo e($product->supplier->name ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Trạng thái:</th>
                        <td>
                            <?php if($product->status == 'draft'): ?>
                                <span class="badge bg-secondary">Bản nháp</span>
                            <?php elseif($product->status == 'published'): ?>
                                <span class="badge bg-success">Đã xuất bản</span>
                            <?php elseif($product->status == 'archived'): ?>
                                <span class="badge bg-danger">Đã lưu trữ</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo e(ucfirst($product->status)); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Ngày tạo:</th>
                        <td><?php echo e($product->created_at->format('d M, Y H:i')); ?></td>
                    </tr>
                    <tr>
                        <th>Cập nhật lần cuối:</th>
                        <td><?php echo e($product->updated_at->format('d M, Y H:i')); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Product Variants -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-tags"></i> Các mẫu mã sản phẩm (<?php echo e($product->variants->count()); ?>)</span>
            </div>
            <div class="card-body">
                <?php if($product->variants->count() > 0): ?>
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
                            <?php $__currentLoopData = $product->variants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><code><?php echo e($variant->sku); ?></code></td>
                                <td><?php echo e($variant->variant_name); ?></td>
                                <td><strong>₫<?php echo e(number_format($variant->price, 2)); ?></strong></td>
                                <td><?php echo e($variant->weight ? $variant->weight . ' kg' : 'N/A'); ?></td>
                                <td><?php echo e($variant->dimensions ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center py-3">No variants available</p>
                <?php endif; ?>
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
                <?php if($product->images->count() > 0): ?>
                    <?php $__currentLoopData = $product->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="mb-3">
                        <!-- Fix image URL handling -->
                        <?php if(Str::startsWith($image->image_url, ['http://', 'https://'])): ?>
                            <img src="<?php echo e($image->image_url); ?>" class="img-fluid rounded" alt="Product Image">
                        <?php else: ?>
                            <img src="<?php echo e(asset(ltrim($image->image_url, '/'))); ?>" class="img-fluid rounded" alt="Product Image">
                        <?php endif; ?>
                        <?php if($image->is_primary): ?>
                            <span class="badge bg-primary mt-2">Primary Image</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-image" style="font-size: 3em; color: #ccc;"></i>
                        <p class="text-muted mt-2">Chưa có hình ảnh nào được tải lên</p>
                    </div>
                <?php endif; ?>
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
                    <div><strong><?php echo e($product->variants->count()); ?></strong></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Hình ảnh</small>
                    <div><strong><?php echo e($product->images->count()); ?></strong></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Phạm vi giá</small>
                    <div>
                        <?php if($product->variants->count() > 0): ?>
                            <strong>₫<?php echo e(number_format($product->variants->min('price'), 2)); ?>

                            - ₫<?php echo e(number_format($product->variants->max('price'), 2)); ?></strong>
                        <?php else: ?>
                            <strong>N/A</strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/products/show.blade.php ENDPATH**/ ?>