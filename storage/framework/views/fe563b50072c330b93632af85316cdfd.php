<?php $__env->startSection('title', 'Lịch sử Xuất Nhập Kho'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1><i class="bi bi-arrow-repeat"></i> Lịch sử Xuất Nhập Kho</h1>
    <p class="text-muted mb-0">Lịch sử đầy đủ về các giao dịch tồn kho</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('manager.inventory.transactions')); ?>" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Kho hàng</label>
                <select class="form-select" name="warehouse_id">
                    <option value="">Tất cả các kho</option>
                    <?php $__currentLoopData = \App\Models\Warehouse::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($warehouse->id); ?>"
                                <?php echo e(request('warehouse_id') == $warehouse->id ? 'selected' : ''); ?>>
                            <?php echo e($warehouse->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Loại</label>
                <select class="form-select" name="type">
                    <option value="">Tất cả các loại</option>
                    <option value="inbound" <?php echo e(request('type') == 'inbound' ? 'selected' : ''); ?>>Nhập kho</option>
                    <option value="outbound" <?php echo e(request('type') == 'outbound' ? 'selected' : ''); ?>>Xuất kho</option>
                    <option value="adjustment" <?php echo e(request('type') == 'adjustment' ? 'selected' : ''); ?>>Điều chỉnh</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo e(request('date_from')); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo e(request('date_to')); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                <a href="<?php echo e(route('manager.inventory.transactions')); ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body">
        <?php if($transactions->count() > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Ngày & Giờ</th>
                        <th>Sản phẩm</th>
                        <th>Kho hàng</th>
                        <th>Loại</th>
                        <th>Số lượng</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                        $type = $transaction->type ?? null;
                        $badge = $type === 'inbound' ? 'bg-success'
                                : ($type === 'outbound' ? 'bg-danger' : 'bg-warning');
                        $typeLabel = $type === 'inbound' ? 'Nhập kho'
                                    : ($type === 'outbound' ? 'Xuất kho' : 'Điều chỉnh');
                        $qty = $transaction->quantity ?? null;
                        ?>

                        <tr>
                        <td><?php echo e($transaction->created_at?->format('d M, Y H:i') ?? '-'); ?></td>

                        <td>
                            <?php echo e($transaction->productVariant?->product?->name ?? 'N/A'); ?>

                            <small class="text-muted">
                            <?php echo e($transaction->productVariant?->sku ?? 'N/A'); ?>

                            </small>
                        </td>

                        <td><?php echo e($transaction->warehouse?->name ?? 'N/A'); ?></td>

                        <td>
                            <span class="badge <?php echo e($badge); ?>"><?php echo e($typeLabel); ?></span>
                        </td>

                        <td>
                            <?php if(is_numeric($qty)): ?>
                            <strong class="<?php echo e($qty > 0 ? 'text-success' : 'text-danger'); ?>">
                                <?php echo e($qty > 0 ? '+' : ''); ?><?php echo e($qty); ?>

                            </strong>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo e($transaction->notes ?? '-'); ?></small></td>
                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
                            <p class="text-muted mt-3">Không tìm thấy giao dịch nào</p>
                        </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            <?php echo e($transactions->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy giao dịch nào</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/inventory/transactions.blade.php ENDPATH**/ ?>