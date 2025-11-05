<?php $__env->startSection('title', 'Quản lý sản phẩm'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> Quản lý sản phẩm</h1>
        <p class="text-muted mb-0">Quản lý danh mục sản phẩm của bạn</p>
    </div>
    <a href="<?php echo e(route('admin.products.create')); ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Thêm sản phẩm mới
    </a>
</div>

<!-- Quick Add Category -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-tags"></i> Thêm danh mục sản phẩm
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('admin.products.categories.store')); ?>" class="row g-3">
            <?php echo csrf_field(); ?>
            <div class="col-md-6">
                <label class="form-label">Tên danh mục *</label>
                <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="VD: Figure, Nendoroid..." value="<?php echo e(old('name')); ?>" required>
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
        <form method="POST" action="<?php echo e(route('admin.products.suppliers.store')); ?>" class="row g-3">
            <?php echo csrf_field(); ?>
            <div class="col-md-5">
                <label class="form-label">Tên nhà cung cấp *</label>
                <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="VD: Good Smile Company" value="<?php echo e(old('name')); ?>" required>
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-5">
                <label class="form-label">Thông tin liên hệ (tuỳ chọn)</label>
                <input type="text" name="contact_info" class="form-control" placeholder="Email/SĐT/Địa chỉ" value="<?php echo e(old('contact_info')); ?>">
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
        <form method="GET" action="<?php echo e(route('admin.products.index')); ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tên sản phẩm..."
                       value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Danh mục</label>
                <select name="category_id" class="form-select">
                    <option value="">Tất cả danh mục</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($category->id); ?>"
                                <?php echo e(request('category_id') == $category->id ? 'selected' : ''); ?>>
                            <?php echo e($category->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Nhà cung cấp</label>
                <select name="supplier_id" class="form-select">
                    <option value="">Tất cả nhà cung cấp</option>
                    <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($supplier->id); ?>"
                                <?php echo e(request('supplier_id') == $supplier->id ? 'selected' : ''); ?>>
                            <?php echo e($supplier->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="draft" <?php echo e(request('status') == 'draft' ? 'selected' : ''); ?>>Bản nháp</option>
                    <option value="published" <?php echo e(request('status') == 'published' ? 'selected' : ''); ?>>Đã xuất bản</option>
                    <option value="archived" <?php echo e(request('status') == 'archived' ? 'selected' : ''); ?>>Đã lưu trữ</option>
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
        <span><i class="bi bi-list"></i> Danh sách sản phẩm (<?php echo e($products->total()); ?> tổng cộng)</span>
    </div>
    <div class="card-body">
        <?php if($products->count() > 0): ?>
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
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong>#<?php echo e($product->id); ?></strong></td>
                        <td>
                            <strong><?php echo e($product->name); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo e(Str::limit($product->description, 50)); ?></small>
                        </td>
                        <td><?php echo e($product->category->name ?? 'N/A'); ?></td>
                        <td><?php echo e($product->supplier->name ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo e($product->variants->count()); ?> mẫu mã</span>
                        </td>
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
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo e(route('admin.products.show', $product->id)); ?>"
                                   class="btn btn-info" title="Xem">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo e(route('admin.products.edit', $product->id)); ?>"
                                   class="btn btn-warning" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?php echo e(route('admin.products.destroy', $product->id)); ?>"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger" title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <?php echo e($products->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy sản phẩm nào</p>
            <a href="<?php echo e(route('admin.products.create')); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tạo sản phẩm đầu tiên
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/products/index.blade.php ENDPATH**/ ?>