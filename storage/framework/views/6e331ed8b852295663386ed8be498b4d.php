<?php $__env->startSection('title', 'Bán hàng trực tiếp'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-cart-check"></i> Bán hàng trực tiếp</h1>
        <p class="text-muted mb-0">Bán hàng cho khách hàng tại chỗ (không giao hàng)</p>
    </div>
    <a href="<?php echo e(route('manager.sales.create')); ?>" class="btn btn-primary btn-lg">
        <i class="bi bi-plus-circle"></i> Tạo đơn bán hàng mới
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('manager.sales.index')); ?>" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Mã đơn hàng..."
                       value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ngày</label>
                <input type="date" class="form-control" name="date"
                       value="<?php echo e(request('date')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Phương thức thanh toán</label>
                <select class="form-select" name="payment_method">
                    <option value="">Tất cả các phương thức</option>
                    <?php $__currentLoopData = $paymentMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($method->id); ?>"
                                <?php echo e(request('payment_method') == $method->id ? 'selected' : ''); ?>>
                            <?php echo e($method->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-body">
        <?php if($sales->count() > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Mặt hàng</th>
                        <th>Tổng số tiền</th>
                        <th>Phương thức thanh toán</th>
                        <th>Ngày & Giờ</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <strong><?php echo e($sale->order_code); ?></strong>
                        </td>
                        <td>
                            <?php echo e($sale->shipping_recipient_name ?? 'Khách hàng tại chỗ'); ?>

                            <?php if($sale->shipping_recipient_phone): ?>
                                <br><small class="text-muted"><?php echo e($sale->shipping_recipient_phone); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo e($sale->orderDetails->count()); ?> mặt hàng</span>
                        </td>
                        <td>
                            <strong class="text-success">₫<?php echo e(number_format($sale->total_amount, 2)); ?></strong>
                        </td>
                        <td>
                            <?php if($sale->payment): ?>
                                <span class="badge bg-primary">
                                    <?php echo e($sale->payment->paymentMethod->name); ?>

                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo e($sale->created_at->format('d M, Y')); ?>

                            <br><small class="text-muted"><?php echo e($sale->created_at->format('H:i A')); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo e(route('manager.sales.show', $sale->id)); ?>"
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
            <?php echo e($sales->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy đơn bán hàng nào</p>
            <a href="<?php echo e(route('manager.sales.create')); ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tạo đơn bán hàng đầu tiên
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/sales/index.blade.php ENDPATH**/ ?>