<?php $__env->startSection('title', 'Quản lý người dùng'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-people"></i> Quản lý người dùng</h1>
        <p class="text-muted mb-0">Quản lý người dùng hệ thống và khách hàng</p>
    </div>
    <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Thêm người dùng mới
    </a>
</div>

<!-- User Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-people-fill"></i>
            </div>
            <h5>Tổng số người dùng</h5>
            <div class="value"><?php echo e($users->total()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5>Quản trị viên</h5>
            <div class="value"><?php echo e($users->filter(fn($u) => $u->role->name == 'admin')->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-info">
                <i class="bi bi-person"></i>
            </div>
            <h5>Người dùng cuối</h5>
            <div class="value"><?php echo e($users->filter(fn($u) => $u->role->name == 'endUser')->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <h5>Hoạt động</h5>
            <div class="value"><?php echo e($users->where('status', 'active')->count()); ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('admin.users.index')); ?>" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tên, email, điện thoại..."
                       value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Vai trò</label>
                <select name="role_id" class="form-select">
                    <option value="">Tất cả vai trò</option>
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($role->id); ?>"
                                <?php echo e(request('role_id') == $role->id ? 'selected' : ''); ?>>
                            <?php echo e($role->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Hoạt động</option>
                    <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Không hoạt động</option>
                    <option value="suspended" <?php echo e(request('status') == 'suspended' ? 'selected' : ''); ?>>Tạm khóa</option>
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

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list"></i> Danh sách người dùng (<?php echo e($users->total()); ?> tổng)</span>
        <a href="<?php echo e(route('admin.users.export')); ?>" class="btn btn-sm btn-success">
            <i class="bi bi-download"></i> Xuất
        </a>
    </div>
    <div class="card-body">
        <?php if($users->count() > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Người dùng</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Tham gia</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong>#<?php echo e($user->id); ?></strong></td>
                        <td>
                            <strong><?php echo e($user->full_name); ?></strong>
                            <?php if($user->email_verified_at): ?>
                                <i class="bi bi-patch-check-fill text-success" title="Đã xác minh"></i>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($user->email); ?></td>
                        <td><?php echo e($user->phone_number ?? 'N/A'); ?></td>
                        <td>
                            <?php if($user->role->name == 'admin'): ?>
                                <span class="badge bg-primary"><?php echo e($user->role->name); ?></span>
                            <?php elseif($user->role->name == 'manager'): ?>
                                <span class="badge bg-warning"><?php echo e($user->role->name); ?></span>
                            <?php else: ?>
                                <span class="badge bg-info"><?php echo e($user->role->name); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($user->status == 'active'): ?>
                                <span class="badge bg-success">Hoạt động</span>
                            <?php elseif($user->status == 'suspended'): ?>
                                <span class="badge bg-danger">Tạm khóa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Không hoạt động</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($user->created_at->format('M d, Y')); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo e(route('admin.users.show', $user->id)); ?>"
                                   class="btn btn-info" title="Xem">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo e(route('admin.users.edit', $user->id)); ?>"
                                   class="btn btn-warning" title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if($user->id != auth()->id()): ?>
                                    <?php if($user->status == 'active'): ?>
                                        <form action="<?php echo e(route('admin.users.suspend', $user->id)); ?>" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>
                                            <button type="submit" class="btn btn-danger" title="Tạm khóa"
                                                    onclick="return confirm('Khóa tạm thời người dùng này?')">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?php echo e(route('admin.users.activate', $user->id)); ?>" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>
                                            <button type="submit" class="btn btn-success" title="Kích hoạt"
                                                    onclick="return confirm('Kích hoạt người dùng này?')">
                                                <i class="bi bi-unlock"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <?php echo e($users->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-people" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy người dùng</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/users/index.blade.php ENDPATH**/ ?>