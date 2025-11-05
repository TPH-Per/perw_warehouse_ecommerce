<?php $__env->startSection('title', 'Tạo người dùng'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1><i class="bi bi-person-plus"></i> Tạo người dùng mới</h1>
    <p class="text-muted mb-0">Thêm người dùng mới vào hệ thống</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Thông tin người dùng
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('admin.users.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?php echo e(old('full_name')); ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Địa chỉ Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo e(old('email')); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number"
                                   value="<?php echo e(old('phone_number')); ?>">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role_id" class="form-label">Vai trò <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Chọn vai trò</option>
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->id); ?>"
                                            <?php echo e(old('role_id') == $role->id ? 'selected' : ''); ?>>
                                        <?php echo e($role->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Tối thiểu 8 ký tự</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo e(old('status') == 'active' ? 'selected' : ''); ?>>Hoạt động</option>
                            <option value="inactive" <?php echo e(old('status') == 'inactive' ? 'selected' : ''); ?>>Không hoạt động</option>
                            <option value="suspended" <?php echo e(old('status') == 'suspended' ? 'selected' : ''); ?>>Tạm khóa</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Tạo người dùng
                        </button>
                        <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-secondary">
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
                <h6>Tạo người dùng</h6>
                <ul class="small">
                    <li>Các trường có dấu <span class="text-danger">*</span> là bắt buộc</li>
                    <li>Email phải là duy nhất trong hệ thống</li>
                    <li>Mật khẩu phải có ít nhất 8 ký tự</li>
                    <li>Vai trò admin có toàn quyền hệ thống</li>
                    <li>Vai trò manager dành cho quản lý kho</li>
                    <li>Vai trò endUser dành cho khách hàng</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/users/create.blade.php ENDPATH**/ ?>