@extends('enduser.layout')

@section('title','Giỏ hàng')

@section('content')
  @section('breadcrumbs')
    @include('enduser.partials.breadcrumbs', ['items' => [
      ['label' => 'Trang chủ', 'url' => route('enduser.home')],
      ['label' => 'Giỏ hàng']
    ]])
  @endsection
  <h2 class="mb-3">Giỏ hàng của bạn</h2>
  @if ($cart->cartDetails->isEmpty())
    <div class="alert alert-info"><i class="bi bi-cart-x me-1"></i>Giỏ hàng trống. <a href="{{ route('enduser.home') }}">Tiếp tục mua sắm</a></div>
  @else
    <div class="table-responsive bg-white rounded shadow-sm">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Sản phẩm</th>
            <th style="width:120px">Giá</th>
            <th style="width:160px">Số lượng</th>
            <th style="width:140px">Tạm tính</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($cart->cartDetails as $d)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  @php $img = optional(optional($d->variant->product)->images->first())->image_url; @endphp
                  <img src="{{ $img }}" class="rounded border me-2" style="width:60px;height:60px;object-fit:cover" />
                  <div>
                    <div class="fw-semibold">{{ optional($d->variant->product)->name }}</div>
                    <div class="text-muted small">SKU: {{ optional($d->variant)->sku }}</div>
                  </div>
                </div>
              </td>
              <td>{{ number_format($d->price ?? 0,0,',','.') }}đ</td>
              <td>
                <form method="post" action="{{ route('enduser.cart.update', $d->id) }}" class="d-flex gap-2">
                  @csrf
                  <input type="number" name="quantity" value="{{ $d->quantity }}" min="0" class="form-control form-control-sm" style="width:90px" />
                  <button type="submit" class="btn btn-outline-secondary btn-sm">Cập nhật</button>
                </form>
              </td>
              <td class="fw-semibold">{{ number_format(($d->price ?? 0) * $d->quantity,0,',','.') }}đ</td>
              <td>
                <form method="post" action="{{ route('enduser.cart.remove', $d->id) }}">
                  @csrf
                  <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end my-3">
      <div class="text-end">
        <div>Tổng số lượng: <strong>{{ $totalItems }}</strong></div>
        <div class="fs-5">Tổng tiền: <span class="price">{{ number_format($totalAmount,0,',','.') }}đ</span></div>
        <a href="{{ route('enduser.checkout') }}" class="btn btn-danger btn-lg mt-2"><i class="bi bi-credit-card me-1"></i>Tiến hành thanh toán</a>
      </div>
    </div>
  @endif
@endsection
