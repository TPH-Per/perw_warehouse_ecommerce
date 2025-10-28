@extends('enduser.layout')

@section('title', $product->name)

@section('content')
  @section('breadcrumbs')
    @include('enduser.partials.breadcrumbs', ['items' => [
      ['label' => 'Trang chủ', 'url' => route('enduser.home')],
      ['label' => $product->name]
    ]])
  @endsection

  <div class="row g-4">
    <div class="col-lg-6">
      @php $primary = optional($product->images->first())->image_url; @endphp
      <div class="bg-white border rounded p-3 text-center">
        <img src="{{ $primary }}" alt="{{ $product->name }}" class="img-fluid" style="max-height:480px; object-fit:contain;" />
      </div>
      @if ($product->images && $product->images->count() > 1)
        <div class="d-flex gap-2 mt-2 flex-wrap">
          @foreach ($product->images as $img)
            <img src="{{ $img->image_url }}" class="rounded border" style="width:70px;height:70px;object-fit:cover" />
          @endforeach
        </div>
      @endif
    </div>
    <div class="col-lg-6">
      <h1 class="h4">{{ $product->name }}</h1>
      <p class="text-muted">{{ $product->description }}</p>

      @auth
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="post" action="{{ route('enduser.cart.add') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-12">
              <label class="form-label">Chọn biến thể</label>
              <select name="product_variant_id" class="form-select" required>
                @foreach ($product->variants as $v)
                  @php
                    $available = ($v->inventories ?? collect())->sum(function($inv){ return max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0)); });
                  @endphp
                  <option value="{{ $v->id }}">SKU {{ $v->sku }} • {{ number_format($v->price,0,',','.') }}đ • Tồn: {{ $available }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label">Số lượng</label>
              <input type="number" name="quantity" value="1" min="1" class="form-control" />
            </div>
            <div class="col-12 col-md-auto">
              <button type="submit" class="btn btn-danger btn-lg"><i class="bi bi-cart-plus me-1"></i>Thêm vào giỏ</button>
            </div>
          </form>
        </div>
      </div>
      @else
        <a href="{{ route('enduser.login') }}" class="btn btn-outline-danger"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập để mua</a>
      @endauth
    </div>
  </div>
@endsection
