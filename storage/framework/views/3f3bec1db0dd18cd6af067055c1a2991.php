<?php $__env->startSection('title', 'Quản lý đơn hàng'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-cart-check"></i> Quản lý đơn hàng</h1>
        <p class="text-muted mb-0">Quản lý đơn hàng và vận chuyển</p>
    </div>
</div>

<!-- Order Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-clock"></i>
            </div>
            <h5>Đang chờ</h5>
            <div class="value"><?php echo e($orders->where('status', 'pending')->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-info">
                <i class="bi bi-gear"></i>
            </div>
            <h5>Đang xử lý</h5>
            <div class="value"><?php echo e($orders->where('status', 'processing')->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-primary">
                <i class="bi bi-truck"></i>
            </div>
            <h5>Đã giao</h5>
            <div class="value"><?php echo e($orders->where('status', 'shipped')->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <h5>Đã nhận</h5>
            <div class="value"><?php echo e($orders->where('status', 'delivered')->count()); ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('admin.orders.index')); ?>" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Mã đơn hàng, tên khách hàng..."
                       value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="pending" <?php echo e(request('status') == 'pending' ? 'selected' : ''); ?>>Đang chờ</option>
                    <option value="processing" <?php echo e(request('status') == 'processing' ? 'selected' : ''); ?>>Đang xử lý</option>
                    <option value="shipped" <?php echo e(request('status') == 'shipped' ? 'selected' : ''); ?>>Đã giao</option>
                    <option value="delivered" <?php echo e(request('status') == 'delivered' ? 'selected' : ''); ?>>Đã nhận</option>
                    <option value="cancelled" <?php echo e(request('status') == 'cancelled' ? 'selected' : ''); ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo e(request('date_from')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo e(request('date_to')); ?>">
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

<!-- Orders Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list"></i> Danh sách đơn hàng (<?php echo e($orders->total()); ?> tổng)</span>
        <a href="<?php echo e(route('admin.orders.export')); ?>" class="btn btn-sm btn-success">
            <i class="bi bi-download"></i> Xuất
        </a>
    </div>
    <div class="card-body">
        <?php if($orders->count() > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Mặt hàng</th>
                        <th>Tổng cộng</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Ngày</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong>#<?php echo e($order->id); ?></strong></td>
                        <td>
                            <strong><?php echo e($order->user->full_name); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo e($order->user->email); ?></small>
                        </td>
                        <td><?php echo e($order->orderDetails->count()); ?> mặt hàng</td>
                        <td><strong>$<?php echo e(number_format($order->payment->amount ?? 0, 2)); ?></strong></td>
                        <td>
                            <?php if($order->status == 'pending'): ?>
                                <span class="badge bg-warning">Đang chờ</span>
                            <?php elseif($order->status == 'processing'): ?>
                                <span class="badge bg-info">Đang xử lý</span>
                            <?php elseif($order->status == 'shipped'): ?>
                                <span class="badge bg-primary">Đã giao</span>
                            <?php elseif($order->status == 'delivered'): ?>
                                <span class="badge bg-success">Đã nhận</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><?php echo e(ucfirst($order->status)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($order->payment): ?>
                                <?php if($order->payment->status == 'completed'): ?>
                                    <span class="badge bg-success">Đã thanh toán</span>
                                <?php elseif($order->payment->status == 'pending'): ?>
                                    <span class="badge bg-warning">Đang chờ</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo e(ucfirst($order->payment->status)); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">Chưa thanh toán</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($order->created_at->format('M d, Y H:i')); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo e(route('admin.orders.show', $order->id)); ?>"
                                   class="btn btn-info" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if($order->status != 'delivered' && $order->status != 'cancelled'): ?>
                                <button type="button" class="btn btn-primary" title="Cập nhật trạng thái"
                                        data-bs-toggle="modal" data-bs-target="#statusModal<?php echo e($order->id); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal<?php echo e($order->id); ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cập nhật trạng thái đơn hàng</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="<?php echo e(route('admin.orders.status.update', $order->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PUT'); ?>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Trạng thái mới</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="pending" <?php echo e($order->status == 'pending' ? 'selected' : ''); ?>>Đang chờ</option>
                                                        <option value="processing" <?php echo e($order->status == 'processing' ? 'selected' : ''); ?>>Đang xử lý</option>
                                                        <option value="shipped" <?php echo e($order->status == 'shipped' ? 'selected' : ''); ?>>Đã giao</option>
                                                        <option value="delivered" <?php echo e($order->status == 'delivered' ? 'selected' : ''); ?>>Đã nhận</option>
                                                        <option value="cancelled" <?php echo e($order->status == 'cancelled' ? 'selected' : ''); ?>>Đã hủy</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                <button type="submit" class="btn btn-primary">Cập nhật trạng thái</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <?php echo e($orders->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy đơn hàng</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/orders/index.blade.php ENDPATH**/ ?>