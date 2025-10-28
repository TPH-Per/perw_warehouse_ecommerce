@extends('enduser.layout')

@section('title','Chi tiết đơn hàng')

@section('content')
  @section('breadcrumbs')
    @include('enduser.partials.breadcrumbs', ['items' => [
      ['label' => 'Trang chủ', 'url' => route('enduser.home')],
      ['label' => 'Đơn hàng của tôi', 'url' => route('enduser.orders')],
      ['label' => $order->order_code]
    ]])
  @endsection
  <h2 class="mb-3">Đơn hàng {{ $order->order_code }}</h2>

  <p>Trạng thái:
    @php
      $badge = 'secondary';
      if ($order->status==='pending') $badge='warning';
      elseif ($order->status==='confirmed') $badge='info';
      elseif ($order->status==='shipped') $badge='primary';
      elseif ($order->status==='delivered') $badge='success';
      elseif ($order->status==='cancelled') $badge='danger';
    @endphp
    <span class="badge bg-{{ $badge }} badge-status">{{ $order->status }}</span>
  </p>
  <p>Người nhận: {{ $order->shipping_recipient_name }} - {{ $order->shipping_recipient_phone }}</p>
  <p>Địa chỉ: {{ $order->shipping_address }}</p>

  <h4 class="mt-3">Sản phẩm</h4>
  <div class="table-responsive bg-white rounded shadow-sm">
  <table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Tên</th><th>SL</th><th>Đơn giá</th><th>Tạm tính</th></tr></thead>
    <tbody>
      @foreach ($order->orderDetails as $d)
        <tr>
          <td>{{ optional($d->variant->product)->name }}</td>
          <td>{{ $d->quantity }}</td>
          <td>{{ number_format($d->price_at_purchase,0,',','.') }}đ</td>
          <td>{{ number_format($d->subtotal,0,',','.') }}đ</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  </div>

  <p>Tạm tính: {{ number_format($order->sub_total,0,',','.') }}đ</p>
  <p>Phí vận chuyển: {{ number_format($order->shipping_fee,0,',','.') }}đ</p>
  <p>Giảm giá: {{ number_format($order->discount_amount,0,',','.') }}đ</p>
  <h4>Tổng: {{ number_format($order->total_amount,0,',','.') }}đ</h4>

  <p>Thanh toán: {{ optional($order->payment->paymentMethod)->name }} - Trạng thái: {{ optional($order->payment)->status }}</p>
  <p>Vận chuyển: {{ optional($order->shipment)->status }}</p>

  <div class="mt-3 d-flex gap-2">
    <a href="{{ route('enduser.orders') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Quay lại</a>
    @if (in_array($order->status, ['pending','confirmed']))
      <!-- Cancel confirm modal trigger -->
      <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-1"></i>Hủy đơn</button>
    @endif
  </div>

  <!-- Modal confirm cancel -->
  <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Xác nhận hủy</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">Bạn có chắc chắn muốn hủy đơn {{ $order->order_code }}?</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <form method="post" action="{{ route('enduser.order.cancel', $order->id) }}" class="d-inline">@csrf<button type="submit" class="btn btn-danger">Hủy đơn</button></form>
        </div>
      </div>
    </div>
  </div>
@endsection
