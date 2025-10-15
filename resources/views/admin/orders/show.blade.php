@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Order Details #{{ $order->order_code }}</h4>
                    <div>
                        <span class="badge bg-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'processing' ? 'info' : ($order->status === 'shipped' ? 'primary' : 'success')) }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Customer Information</h5>
                            <p><strong>Name:</strong> {{ $order->user->full_name }}</p>
                            <p><strong>Email:</strong> {{ $order->user->email }}</p>
                            <p><strong>Phone:</strong> {{ $order->shipping_recipient_phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                            <p><strong>Order Code:</strong> {{ $order->order_code }}</p>
                            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Shipping Address</h5>
                            <p>{{ $order->shipping_address }}</p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Order Items</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
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
                                        <th>Subtotal:</th>
                                        <td>{{ number_format($order->sub_total, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <th>Shipping Fee:</th>
                                        <td>{{ number_format($order->shipping_fee, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <th>Discount:</th>
                                        <td>-{{ number_format($order->discount_amount, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <th><strong>Total:</strong></th>
                                        <td><strong>{{ number_format($order->total_amount, 0, ',', '.') }} VND</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Back to Orders</a>
                    @if($order->status === 'pending')
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processOrderModal">Process Order</button>
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
                <h5 class="modal-title" id="processOrderModalLabel">Process Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.orders.status.update', $order) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Update Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
