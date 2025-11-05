<?php $__env->startSection('title', 'Đơn hàng vận chuyển'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1><i class="bi bi-truck"></i> Đơn hàng vận chuyển</h1>
    <p class="text-muted mb-0">Quản lý đơn hàng vận chuyển của khách hàng</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('manager.orders.index')); ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Mã đơn hàng hoặc khách hàng..."
                       value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="status">
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
                <input type="date" class="form-control" name="date_from" value="<?php echo e(request('date_from')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="date_to" value="<?php echo e(request('date_to')); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                <a href="<?php echo e(route('manager.orders.index')); ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <?php if($orders->count() > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Mặt hàng</th>
                        <th>Tổng cộng</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt hàng</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong><?php echo e($order->order_code); ?></strong></td>
                        <td>
                            <?php echo e($order->shipping_recipient_name); ?>

                            <br>
                            <small class="text-muted"><?php echo e($order->shipping_recipient_phone); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo e($order->orderDetails->count()); ?> mặt hàng</span>
                        </td>
                        <td><strong class="text-success">₫<?php echo e(number_format($order->total_amount, 2)); ?></strong></td>
                        <td>
                            <?php if($order->status == 'pending'): ?>
                                <span class="badge bg-warning">Đang chờ</span>
                            <?php elseif($order->status == 'processing'): ?>
                                <span class="badge bg-info">Đang xử lý</span>
                            <?php elseif($order->status == 'shipped'): ?>
                                <span class="badge bg-primary">Đã giao</span>
                            <?php elseif($order->status == 'delivered'): ?>
                                <span class="badge bg-success">Đã nhận</span>
                            <?php elseif($order->status == 'cancelled'): ?>
                                <span class="badge bg-danger">Đã hủy</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($order->created_at->format('d M, Y')); ?></td>
                        <td>
                            <a href="<?php echo e(route('manager.orders.show', $order->id)); ?>"
                               class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            <?php echo e($orders->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy đơn hàng vận chuyển nào</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/orders/index.blade.php ENDPATH**/ ?>