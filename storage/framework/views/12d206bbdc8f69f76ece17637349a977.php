<?php $__env->startSection('title', 'Chi tiết đơn bán hàng'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-receipt"></i> Chi tiết đơn bán hàng</h1>
        <p class="text-muted mb-0">Đơn hàng <?php echo e($order->order_code); ?></p>
    </div>
    <a href="<?php echo e(route('manager.sales.index')); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại danh sách bán hàng
    </a>
</div>

<div class="row">
    <!-- Order Information -->
    <div class="col-lg-8">
        <!-- Customer Information Card -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-person"></i> Thông tin khách hàng
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Tên:</strong> <?php echo e($order->shipping_recipient_name ?? 'Khách hàng tại chỗ'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Số điện thoại:</strong> <?php echo e($order->shipping_recipient_phone ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-basket"></i> Các mặt hàng trong đơn hàng
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Mã sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Tổng phụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $order->orderDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($detail->productVariant->product->name); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo e($detail->productVariant->name); ?></small>
                                </td>
                                <td><?php echo e($detail->productVariant->sku); ?></td>
                                <td>₫<?php echo e(number_format($detail->price_at_purchase, 2)); ?></td>
                                <td><?php echo e($detail->quantity); ?></td>
                                <td><strong>₫<?php echo e(number_format($detail->subtotal, 2)); ?></strong></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Tổng phụ:</strong></td>
                                <td><strong>₫<?php echo e(number_format($order->sub_total, 2)); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Phí vận chuyển:</strong></td>
                                <td><strong>₫<?php echo e(number_format($order->shipping_fee, 2)); ?></strong></td>
                            </tr>
                            <?php if($order->discount_amount > 0): ?>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Giảm giá:</strong></td>
                                <td><strong class="text-danger">-₫<?php echo e(number_format($order->discount_amount, 2)); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-success">
                                <td colspan="4" class="text-end"><h5>Tổng cộng:</h5></td>
                                <td><h5 class="text-success">₫<?php echo e(number_format($order->total_amount, 2)); ?></h5></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inventory Transactions -->
        <?php if($order->inventoryTransactions->count() > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-arrow-repeat"></i> Giao dịch kho hàng
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Kho hàng</th>
                                <th>Thay đổi số lượng</th>
                                <th>Số lượng sau</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $order->inventoryTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <?php echo e($transaction->productVariant->product->name); ?>

                                    <br>
                                    <small class="text-muted"><?php echo e($transaction->productVariant->sku); ?></small>
                                </td>
                                <td><?php echo e($transaction->warehouse->name); ?></td>
                                <td>
                                    <span class="badge bg-danger"><?php echo e($transaction->quantity); ?></span>
                                </td>
                                <td>N/A</td>
                                <td><small><?php echo e($transaction->notes); ?></small></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Order Summary Sidebar -->
    <div class="col-lg-4">
        <!-- Order Status Card -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <i class="bi bi-check-circle"></i> Trạng thái đơn hàng
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Trạng thái:</strong>
                    <span class="badge bg-success"><?php echo e(ucfirst($order->status)); ?></span>
                </p>
                <p class="mb-2">
                    <strong>Ngày đặt hàng:</strong><br>
                    <?php echo e($order->created_at->format('d M, Y H:i A')); ?>

                </p>
                <p class="mb-0">
                    <strong>Loại bán hàng:</strong><br>
                    <span class="badge bg-info">Bán hàng trực tiếp (Tại chỗ)</span>
                </p>
            </div>
        </div>

        <?php if(!($order->payment && $order->payment->status === 'completed')): ?>
        <!-- Online Payment Actions -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-credit-card"></i> Thanh toán trực tuyến
            </div>
            <div class="card-body">
                <a href="<?php echo e(route('payment.vnpay.create', ['order' => $order->id])); ?>" class="btn btn-primary w-100">
                    <i class="bi bi-credit-card"></i> Thanh toán VNPAY
                </a>
                <?php if(app()->environment('local')): ?>
                <a href="<?php echo e(route('payment.testqr.show', ['order' => $order->id])); ?>" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-qr-code"></i> Test QR (Local)
                </a>
                <?php endif; ?>
                <small class="text-muted d-block mt-2">Hỗ trợ thẻ nội địa/QR, chuyển hướng qua cổng VNPAY.</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Information Card -->
        <?php if($order->payment): ?>
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-credit-card"></i> Thông tin thanh toán
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Phương thức:</strong><br>
                    <?php echo e($order->payment->paymentMethod->name); ?>

                </p>
                <p class="mb-2">
                    <strong>Số tiền:</strong><br>
                    <span class="text-success h5">₫<?php echo e(number_format($order->payment->amount, 2)); ?></span>
                </p>
                <p class="mb-2">
                    <strong>Trạng thái:</strong>
                    <span class="badge bg-success"><?php echo e(ucfirst($order->payment->status)); ?></span>
                </p>
                <p class="mb-0">
                    <strong>Mã giao dịch:</strong><br>
                    <small class="text-muted"><?php echo e($order->payment->transaction_code); ?></small>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Print Receipt Button -->
        <div class="card">
            <div class="card-body">
                <button onclick="window.print()" class="btn btn-outline-primary w-100">
                    <i class="bi bi-printer"></i> In hóa đơn
                </button>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('styles'); ?>
<style>
@media print {
    .page-header a,
    .btn,
    .sidebar {
        display: none !important;
    }

    main {
        padding: 0 !important;
    }

    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }

    .page-header {
        border-bottom: 2px solid #333 !important;
        padding-bottom: 10px !important;
        margin-bottom: 20px !important;
    }
}
</style>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/sales/show.blade.php ENDPATH**/ ?>