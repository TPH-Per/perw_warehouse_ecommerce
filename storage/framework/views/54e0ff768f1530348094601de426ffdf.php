<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Chi tiết đơn hàng #<?php echo e($order->order_code); ?></h4>
                    <div>
                        <span class="badge bg-<?php echo e($order->status === 'pending' ? 'warning' : ($order->status === 'processing' ? 'info' : ($order->status === 'shipped' ? 'primary' : 'success'))); ?>">
                            <?php echo e($order->status == 'pending' ? 'Đang chờ' : ($order->status == 'processing' ? 'Đang xử lý' : ($order->status == 'shipped' ? 'Đã giao' : ($order->status == 'delivered' ? 'Đã nhận' : ucfirst($order->status))))); ?>

                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin khách hàng</h5>
                            <p><strong>Tên:</strong> <?php echo e($order->user->full_name); ?></p>
                            <p><strong>Email:</strong> <?php echo e($order->user->email); ?></p>
                            <p><strong>Số điện thoại:</strong> <?php echo e($order->shipping_recipient_phone); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Thông tin đơn hàng</h5>
                            <p><strong>Ngày đặt hàng:</strong> <?php echo e($order->created_at->format('M d, Y H:i')); ?></p>
                            <p><strong>Mã đơn hàng:</strong> <?php echo e($order->order_code); ?></p>
                            <p><strong>Trạng thái:</strong> <?php echo e($order->status == 'pending' ? 'Đang chờ' : ($order->status == 'processing' ? 'Đang xử lý' : ($order->status == 'shipped' ? 'Đã giao' : ($order->status == 'delivered' ? 'Đã nhận' : ucfirst($order->status))))); ?></p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Địa chỉ giao hàng</h5>
                            <p><?php echo e($order->shipping_address); ?></p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Sản phẩm trong đơn</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>SKU</th>
                                            <th>Giá</th>
                                            <th>Số lượng</th>
                                            <th>Tổng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $order->orderDetails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($detail->productVariant->product->name); ?> - <?php echo e($detail->productVariant->name); ?></td>
                                            <td><?php echo e($detail->productVariant->sku); ?></td>
                                            <td><?php echo e(number_format($detail->price, 0, ',', '.')); ?> VND</td>
                                            <td><?php echo e($detail->quantity); ?></td>
                                            <td><?php echo e(number_format($detail->price * $detail->quantity, 0, ',', '.')); ?> VND</td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6 offset-md-6">
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <th>Tạm tính:</th>
                                        <td><?php echo e(number_format($order->sub_total, 0, ',', '.')); ?> VND</td>
                                    </tr>
                                    <tr>
                                        <th>Phí vận chuyển:</th>
                                        <td><?php echo e(number_format($order->shipping_fee, 0, ',', '.')); ?> VND</td>
                                    </tr>
                                    <tr>
                                        <th>Giảm giá:</th>
                                        <td>-<?php echo e(number_format($order->discount_amount, 0, ',', '.')); ?> VND</td>
                                    </tr>
                                    <tr>
                                        <th><strong>Tổng cộng:</strong></th>
                                        <td><strong><?php echo e(number_format($order->total_amount, 0, ',', '.')); ?> VND</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo e(route('admin.orders.index')); ?>" class="btn btn-secondary">Quay lại Đơn hàng</a>
                    <?php if(!($order->payment && $order->payment->status === 'completed')): ?>
                    <a href="<?php echo e(route('payment.vnpay.create', ['order' => $order->id])); ?>" class="btn btn-outline-primary">Thanh toán VNPAY</a>
                    <form action="<?php echo e(route('admin.orders.payment.cod', ['order' => $order->id])); ?>" method="POST" style="display: inline-block;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-outline-success">Thanh toán khi nhận hàng</button>
                    </form>
                    <?php if(app()->environment('local')): ?>
                    <a href="<?php echo e(route('payment.testqr.show', ['order' => $order->id])); ?>" class="btn btn-outline-dark">Test QR (Local)</a>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if($order->status === 'pending'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processOrderModal">Xử lý đơn hàng</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Order Modal -->
<div class="modal fade" id="processOrderModal" tabindex="-1" aria-labelledby="processOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processOrderModalLabel">Xử lý đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="<?php echo e(route('admin.orders.status.update', $order)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Cập nhật trạng thái</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="processing">Đang xử lý</option>
                            <option value="shipped">Đã giao</option>
                            <option value="delivered">Đã nhận</option>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/orders/show.blade.php ENDPATH**/ ?>