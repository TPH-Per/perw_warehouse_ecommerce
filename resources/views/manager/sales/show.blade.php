@extends('layouts.manager')

@section('title', 'Chi tiết đơn bán hàng')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-receipt"></i> Chi tiết đơn bán hàng</h1>
        <p class="text-muted mb-0">Đơn hàng {{ $order->order_code }}</p>
    </div>
    <a href="{{ route('manager.sales.index') }}" class="btn btn-secondary">
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
                        <p><strong>Tên:</strong> {{ $order->shipping_recipient_name ?? 'Khách hàng tại chỗ' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Số điện thoại:</strong> {{ $order->shipping_recipient_phone ?? 'N/A' }}</p>
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
                            @foreach($order->orderDetails as $detail)
                            <tr>
                                <td>
                                    <strong>{{ $detail->productVariant->product->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $detail->productVariant->name }}</small>
                                </td>
                                <td>{{ $detail->productVariant->sku }}</td>
                                <td>₫{{ number_format($detail->price_at_purchase, 2) }}</td>
                                <td>{{ $detail->quantity }}</td>
                                <td><strong>₫{{ number_format($detail->subtotal, 2) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Tổng phụ:</strong></td>
                                <td><strong>₫{{ number_format($order->sub_total, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Phí vận chuyển:</strong></td>
                                <td><strong>₫{{ number_format($order->shipping_fee, 2) }}</strong></td>
                            </tr>
                            @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="text-end"><strong>Giảm giá:</strong></td>
                                <td><strong class="text-danger">-₫{{ number_format($order->discount_amount, 2) }}</strong></td>
                            </tr>
                            @endif
                            <tr class="table-success">
                                <td colspan="4" class="text-end"><h5>Tổng cộng:</h5></td>
                                <td><h5 class="text-success">₫{{ number_format($order->total_amount, 2) }}</h5></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inventory Transactions -->
        @if($order->inventoryTransactions->count() > 0)
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
                            @foreach($order->inventoryTransactions as $transaction)
                            <tr>
                                <td>
                                    {{ $transaction->productVariant->product->name }}
                                    <br>
                                    <small class="text-muted">{{ $transaction->productVariant->sku }}</small>
                                </td>
                                <td>{{ $transaction->warehouse->name }}</td>
                                <td>
                                    <span class="badge bg-danger">{{ $transaction->quantity }}</span>
                                </td>
                                <td>N/A</td>
                                <td><small>{{ $transaction->notes }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
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
                    <span class="badge bg-success">{{ ucfirst($order->status) }}</span>
                </p>
                <p class="mb-2">
                    <strong>Ngày đặt hàng:</strong><br>
                    {{ $order->created_at->format('d M, Y H:i A') }}
                </p>
                <p class="mb-0">
                    <strong>Loại bán hàng:</strong><br>
                    <span class="badge bg-info">Bán hàng trực tiếp (Tại chỗ)</span>
                </p>
            </div>
        </div>

        @if(!($order->payment && $order->payment->status === 'completed'))
        <!-- Online Payment Actions -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-credit-card"></i> Thanh toán trực tuyến
            </div>
            <div class="card-body">
                <a href="{{ route('payment.vnpay.create', ['order' => $order->id]) }}" class="btn btn-primary w-100">
                    <i class="bi bi-credit-card"></i> Thanh toán VNPAY
                </a>
                @if(app()->environment('local'))
                <a href="{{ route('payment.testqr.show', ['order' => $order->id]) }}" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-qr-code"></i> Test QR (Local)
                </a>
                @endif
                <small class="text-muted d-block mt-2">Hỗ trợ thẻ nội địa/QR, chuyển hướng qua cổng VNPAY.</small>
            </div>
        </div>
        @endif

        <!-- Payment Information Card -->
        @if($order->payment)
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-credit-card"></i> Thông tin thanh toán
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Phương thức:</strong><br>
                    {{ $order->payment->paymentMethod->name }}
                </p>
                <p class="mb-2">
                    <strong>Số tiền:</strong><br>
                    <span class="text-success h5">₫{{ number_format($order->payment->amount, 2) }}</span>
                </p>
                <p class="mb-2">
                    <strong>Trạng thái:</strong>
                    <span class="badge bg-success">{{ ucfirst($order->payment->status) }}</span>
                </p>
                <p class="mb-0">
                    <strong>Mã giao dịch:</strong><br>
                    <small class="text-muted">{{ $order->payment->transaction_code }}</small>
                </p>
            </div>
        </div>
        @endif

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

@push('styles')
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
@endpush
@endsection
