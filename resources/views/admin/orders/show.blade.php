@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Chi tiết đơn hàng #{{ $order->order_code }}</h4>
                    <div>
                        <span class="badge bg-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'processing' ? 'info' : ($order->status === 'shipped' ? 'primary' : 'success')) }}">
                            {{ $order->status == 'pending' ? 'Đang chờ' : ($order->status == 'processing' ? 'Đang xử lý' : ($order->status == 'shipped' ? 'Đã giao' : ($order->status == 'delivered' ? 'Đã nhận' : ucfirst($order->status)))) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin khách hàng</h5>
                            <p><strong>Tên:</strong> {{ $order->user->full_name }}</p>
                            <p><strong>Email:</strong> {{ $order->user->email }}</p>
                            <p><strong>Số điện thoại:</strong> {{ $order->shipping_recipient_phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Thông tin đơn hàng</h5>
                            <p><strong>Ngày đặt hàng:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                            <p><strong>Mã đơn hàng:</strong> {{ $order->order_code }}</p>
                            <p><strong>Trạng thái:</strong> {{ $order->status == 'pending' ? 'Đang chờ' : ($order->status == 'processing' ? 'Đang xử lý' : ($order->status == 'shipped' ? 'Đã giao' : ($order->status == 'delivered' ? 'Đã nhận' : ucfirst($order->status)))) }}</p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Địa chỉ giao hàng</h5>
                            <p>{{ $order->shipping_address }}</p>
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
                                        @foreach($order->orderDetails as $detail)
                                        <tr>
                                            <td>{{ $detail->productVariant->product->name }} - {{ $detail->productVariant->name }}</td>
                                            <td>{{ $detail->productVariant->sku }}</td>
                                            <td>{{ number_format($detail->price, 0, ',', '.') }} VND</td>
                                            <td>{{ $detail->quantity }}</td>
                                            <td>{{ number_format($detail->price * $detail->quantity, 0, ',', '.') }} VND</td>
                                        </tr>
                                        @endforeach
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
                                        <td>{{ number_format($order->sub_total, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <th>Phí vận chuyển:</th>
                                        <td>{{ number_format($order->shipping_fee, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <th>Giảm giá:</th>
                                        <td>-{{ number_format($order->discount_amount, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <th><strong>Tổng cộng:</strong></th>
                                        <td><strong>{{ number_format($order->total_amount, 0, ',', '.') }} VND</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Quay lại Đơn hàng</a>
                    @if(!($order->payment && $order->payment->status === 'completed'))
                    <a href="{{ route('payment.vnpay.create', ['order' => $order->id]) }}" class="btn btn-outline-primary">Thanh toán VNPAY</a>
                    <a href="{{ route('payment.checkoutvn.create', ['order' => $order->id]) }}" class="btn btn-outline-secondary">Thanh toán Checkout.vn</a>
                    @if(app()->environment('local'))
                    <a href="{{ route('payment.testqr.show', ['order' => $order->id]) }}" class="btn btn-outline-dark">Test QR (Local)</a>
                    @endif
                    @endif
                    @if($order->status === 'pending')
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processOrderModal">Xử lý đơn hàng</button>
                    @endif
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
            <form action="{{ route('admin.orders.status.update', $order) }}" method="POST">
                @csrf
                @method('PUT')
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
@endsection
