@extends('enduser.layout')

@section('title','Thanh toán')

@section('content')
  @section('breadcrumbs')
    @include('enduser.partials.breadcrumbs', ['items' => [
      ['label' => 'Trang chủ', 'url' => route('enduser.home')],
      ['label' => 'Giỏ hàng', 'url' => route('enduser.cart')],
      ['label' => 'Thanh toán']
    ]])
  @endsection
  <h2 class="mb-3">Thanh toán</h2>

  @if ($cart->cartDetails->isEmpty())
    <p>Giỏ hàng trống. <a href="{{ route('enduser.home') }}">Tiếp tục mua sắm</a></p>
  @else
    <div class="row g-3">
      <section class="col-lg-8">
        <form method="post" action="{{ route('enduser.checkout.place') }}" class="card shadow-sm">
          <div class="card-body">
          @csrf
          <div class="row g-3">
            <div class="col-12">
              <h5 class="mb-2">Thông tin giao hàng</h5>
            </div>
            <div class="col-md-6">
              <label class="form-label">Họ tên người nhận</label>
              <input type="text" name="shipping_recipient_name" value="{{ old('shipping_recipient_name', auth()->user()->name) }}" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label class="form-label">Số điện thoại</label>
              <input type="text" name="shipping_recipient_phone" value="{{ old('shipping_recipient_phone') }}" class="form-control" required />
            </div>
            <div class="col-12">
              <label class="form-label">Địa chỉ giao hàng</label>
              <textarea name="shipping_address" class="form-control" rows="3" required>{{ old('shipping_address') }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Ghi chú</label>
              <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phương thức thanh toán</label>
              <select name="payment_method_id" class="form-select" required>
                @foreach ($paymentMethods as $pm)
                  <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phương thức vận chuyển</label>
              <select name="shipping_method_id" class="form-select">
                @foreach ($shippingMethods as $sm)
                  <option value="{{ $sm->id }}">{{ $sm->name ?? ('Phương thức #'.$sm->id) }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-danger btn-lg"><i class="bi bi-bag-check me-1"></i>Đặt hàng</button>
            </div>
          </div>
          </div>
        </form>
      </section>

      <aside class="col-lg-4">
        <div class="card shadow-sm sticky-aside">
          <div class="card-body">
          <h5 class="mb-3">Tóm tắt đơn hàng</h5>
          <ul class="list-unstyled">
            @foreach ($cart->cartDetails as $d)
              <li class="d-flex justify-content-between border-bottom py-2">
                <span>{{ optional($d->variant->product)->name }} × {{ $d->quantity }}</span>
                <span class="fw-semibold">{{ number_format(($d->price ?? 0) * $d->quantity,0,',','.') }}đ</span>
              </li>
            @endforeach
          </ul>
          <div class="border-top pt-2 mt-2">
            <div class="d-flex justify-content-between"><span>Tạm tính</span><strong>{{ number_format($subTotal,0,',','.') }}đ</strong></div>
            <div class="d-flex justify-content-between"><span>Phí vận chuyển</span><strong>{{ number_format($shippingFee,0,',','.') }}đ</strong></div>
            <div class="d-flex justify-content-between"><span>Giảm giá</span><strong>{{ number_format($discountAmount,0,',','.') }}đ</strong></div>
            <div class="d-flex justify-content-between fs-5 mt-2"><span>Tổng</span><span class="price">{{ number_format($totalAmount,0,',','.') }}đ</span></div>
          </div>
        </div>
        </div>
      </aside>
    </div>
  @endif
@endsection
