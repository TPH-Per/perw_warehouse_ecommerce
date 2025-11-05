<?php $__env->startSection('title', 'Chi tiết đơn hàng'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-receipt"></i> Đơn hàng <?php echo e($order->order_code); ?></h1>
        <p class="text-muted mb-0">Chi tiết và quản lý đơn hàng vận chuyển</p>
    </div>
    <a href="<?php echo e(route('manager.orders.index')); ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<div class="row">
    <!-- Order Details -->
    <div class="col-lg-8">
        <!-- Customer & Shipping Info -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-person-circle"></i> Thông tin Khách hàng & Vận chuyển
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Tên khách hàng:</strong><br>
                        <?php echo e($order->shipping_recipient_name); ?>

                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Số điện thoại:</strong><br>
                        <?php echo e($order->shipping_recipient_phone); ?>

                    </div>
                    <div class="col-12 mb-3">
                        <strong>Địa chỉ giao hàng:</strong><br>
                        <?php echo e($order->shipping_address); ?>

                    </div>
                    <?php if($order->user): ?>
                    <div class="col-md-6">
                        <strong>Tài khoản khách hàng:</strong><br>
                        <?php echo e($order->user->email); ?>

                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items -->
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

        <!-- Shipment Information -->
        <?php if($order->shipment): ?>
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <i class="bi bi-box"></i> Thông tin vận chuyển
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Số theo dõi:</strong><br>
                        <?php echo e($order->shipment->tracking_number ?? 'Chưa được gán'); ?>

                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Đơn vị vận chuyển:</strong><br>
                        <?php echo e($order->shipment->carrier ?? 'N/A'); ?>

                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Trạng thái vận chuyển:</strong><br>
                        <span class="badge bg-info"><?php echo e(ucfirst($order->shipment->status)); ?></span>
                    </div>
                    <?php if($order->shipment->delivered_at): ?>
                    <div class="col-md-6 mb-3">
                        <strong>Thời gian giao hàng:</strong><br>
                        <?php echo e($order->shipment->delivered_at->format('d M, Y H:i A')); ?>

                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions Sidebar -->
    <div class="col-lg-4">
        <!-- Order Status -->
        <div class="card mb-3">
            <div class="card-header
                <?php if($order->status == 'delivered'): ?> bg-success
                <?php elseif($order->status == 'shipped'): ?> bg-primary
                <?php elseif($order->status == 'cancelled'): ?> bg-danger
                <?php else: ?> bg-warning
                <?php endif; ?>
                text-white">
                <i class="bi bi-flag"></i> Trạng thái đơn hàng
            </div>
            <div class="card-body">
                <p class="mb-3">
                    <strong>Trạng thái hiện tại:</strong><br>
                    <span class="badge
                        <?php if($order->status == 'pending'): ?> bg-warning
                        <?php elseif($order->status == 'processing'): ?> bg-info
                        <?php elseif($order->status == 'shipped'): ?> bg-primary
                        <?php elseif($order->status == 'delivered'): ?> bg-success
                        <?php elseif($order->status == 'cancelled'): ?> bg-danger
                        <?php endif; ?>
                        fs-6">
                        <?php echo e(ucfirst($order->status)); ?>

                    </span>
                </p>

                <?php if($order->status != 'cancelled' && $order->status != 'delivered'): ?>
                <form method="POST" action="<?php echo e(route('manager.orders.status.update', $order->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="mb-3">
                        <label class="form-label">Cập nhật trạng thái</label>
                        <select class="form-select" name="status" required>
                            <option value="">Chọn trạng thái mới...</option>
                            <?php if($order->status == 'pending'): ?>
                                <option value="processing">Đang xử lý</option>
                                <option value="cancelled">Hủy đơn hàng</option>
                            <?php elseif($order->status == 'processing'): ?>
                                <option value="shipped">Đánh dấu đã giao</option>
                                <option value="cancelled">Hủy đơn hàng</option>
                            <?php elseif($order->status == 'shipped'): ?>
                                <option value="delivered">Đánh dấu đã nhận</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle"></i> Cập nhật trạng thái
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Đơn hàng đang <?php echo e($order->status); ?>. Không cho phép cập nhật thêm.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Information -->
        <?php if($order->payment): ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
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

        <!-- Tracking Information -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-geo-alt"></i> Cập nhật thông tin theo dõi
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('manager.orders.tracking.update', $order->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <div class="mb-3">
                        <label class="form-label">Số theo dõi</label>
                        <input type="text" class="form-control" name="tracking_number"
                               value="<?php echo e($order->shipment->tracking_number ?? ''); ?>"
                               placeholder="Nhập số theo dõi...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đơn vị vận chuyển</label>
                        <input type="text" class="form-control" name="carrier"
                               value="<?php echo e($order->shipment->carrier ?? ''); ?>"
                               placeholder="Nhập tên đơn vị vận chuyển...">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Lưu thông tin theo dõi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/orders/show.blade.php ENDPATH**/ ?>