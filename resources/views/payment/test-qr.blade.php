@extends('layouts.manager')

@section('title', 'Thanh toán Test QR (Local)')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-qr-code"></i> Test QR thanh toán (Local)</h1>
        <p class="text-muted mb-0">Đơn hàng {{ $order->order_code }} — Tổng: ₫{{ number_format($order->total_amount, 0, ',', '.') }}</p>
    </div>
    <a href="{{ route('manager.sales.show', $order->id) }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại đơn hàng
    </a>
    </div>

<div class="row mt-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">QR Code</div>
            <div class="card-body text-center">
                <div id="qrcode" class="d-inline-block border p-3"></div>
                <p class="mt-3 small text-muted">Quét bằng camera để mở nội dung mô phỏng thanh toán.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Thông tin</div>
            <div class="card-body">
                <ul class="mb-3">
                    <li>Mã đơn: <strong>{{ $order->order_code }}</strong></li>
                    <li>Số tiền: <strong>₫{{ number_format($order->total_amount, 0, ',', '.') }}</strong></li>
                </ul>
                <form method="POST" action="{{ route('payment.testqr.simulate', $order->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle"></i> Đánh dấu ĐÃ THANH TOÁN (mô phỏng)
                    </button>
                </form>
                <p class="mt-2 small text-muted">Chỉ dùng để test nội bộ ở môi trường local. Không sử dụng trên production.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
  // Nội dung mô phỏng trong QR
  const payload = {
    type: 'perw-pay-test',
    order_code: @json($order->order_code),
    amount_vnd: @json((int) round($order->total_amount)),
    hint: 'Đây là QR mô phỏng thanh toán local'
  };
  new QRCode(document.getElementById('qrcode'), {
    text: JSON.stringify(payload),
    width: 240,
    height: 240,
    correctLevel: QRCode.CorrectLevel.M
  });
</script>
@endpush
@endsection

