@extends('enduser.layout')

@section('title','Trang chủ')

@section('content')
  <div class="row g-3">
    <aside class="col-lg-3">
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <form method="get" class="d-flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm kiếm sản phẩm..." />
            <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
          </form>
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="mb-2">Danh mục</h6>
          <ul class="list-unstyled mb-0">
            <li><a class="text-decoration-none {{ request('category_id') ? '' : 'fw-semibold' }}" href="{{ route('enduser.home', array_filter(['q'=>request('q'),'sort'=>request('sort')])) }}">Tất cả</a></li>
            @foreach ($categories as $c)
              @php $active = (string)request('category_id') === (string)$c->id; @endphp
              <li class="mt-1">
                <a class="text-decoration-none {{ $active ? 'fw-semibold text-danger' : '' }}" href="{{ route('enduser.home', array_filter(['category_id'=>$c->id,'q'=>request('q'),'sort'=>request('sort')])) }}">{{ $c->name }}</a>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
      <div class="card shadow-sm mt-3">
        <div class="card-body">
          <h6 class="mb-2">Sắp xếp</h6>
          <div class="d-grid gap-2">
            <a class="btn btn-sm {{ request('sort')==='newest'||!request('sort') ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('enduser.home', array_filter(['category_id'=>request('category_id'),'q'=>request('q'),'sort'=>'newest'])) }}">Mới nhất</a>
            <a class="btn btn-sm {{ request('sort')==='name' ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('enduser.home', array_filter(['category_id'=>request('category_id'),'q'=>request('q'),'sort'=>'name'])) }}">Tên A-Z</a>
            <a class="btn btn-sm {{ request('sort')==='price_low' ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('enduser.home', array_filter(['category_id'=>request('category_id'),'q'=>request('q'),'sort'=>'price_low'])) }}">Giá thấp → cao</a>
            <a class="btn btn-sm {{ request('sort')==='price_high' ? 'btn-danger' : 'btn-outline-secondary' }}" href="{{ route('enduser.home', array_filter(['category_id'=>request('category_id'),'q'=>request('q'),'sort'=>'price_high'])) }}">Giá cao → thấp</a>
          </div>
        </div>
      </div>
    </aside>
    <section class="col-lg-9">
      <h2 class="mb-3">Sản phẩm</h2>
      <div class="row g-3">
        @forelse ($products as $p)
          @php
            $img = optional($p->images->first())->image_url;
            $minPrice = $p->variants && $p->variants->count() ? $p->variants->min('price') : 0;
            $minOriginal = $p->variants && $p->variants->count() ? $p->variants->whereNotNull('original_price')->min('original_price') : null;
            $discountPct = ($minOriginal && $minOriginal > $minPrice) ? round((($minOriginal - $minPrice)/$minOriginal)*100) : null;
            $available = 0;
            foreach (($p->variants ?? []) as $v) {
              $invAvail = 0;
              foreach (($v->inventories ?? []) as $inv) { $invAvail += max(0, ($inv->quantity_on_hand ?? 0) - ($inv->quantity_reserved ?? 0)); }
              $available += $invAvail;
            }
          @endphp
          <div class="col-6 col-md-4 col-lg-4">
            <div class="card card-hover h-100 position-relative">
              @if($discountPct)
                <span class="badge bg-danger position-absolute" style="top:.5rem;left:.5rem">-{{ $discountPct }}%</span>
              @endif
              @if($available<=0)
                <span class="badge bg-secondary position-absolute" style="top:.5rem;right:.5rem">Hết hàng</span>
              @endif
              <a href="{{ route('enduser.product', $p->id) }}" class="text-decoration-none text-dark">
                <img src="{{ $img }}" class="card-img-top" alt="{{ $p->name }}">
                <div class="card-body">
                  <h6 class="card-title text-truncate" title="{{ $p->name }}">{{ $p->name }}</h6>
                  <div class="d-flex align-items-baseline gap-2">
                    <span class="price">{{ number_format($minPrice,0,',','.') }}đ</span>
                    @if($minOriginal && $minOriginal>$minPrice)
                      <small class="text-muted text-decoration-line-through">{{ number_format($minOriginal,0,',','.') }}đ</small>
                    @endif
                  </div>
                </div>
              </a>
            </div>
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-info">Không tìm thấy sản phẩm phù hợp.</div>
          </div>
        @endforelse
      </div>

      <div class="mt-3">{{ $products->links('vendor.pagination.bootstrap-5') }}</div>
    </section>
  </div>
@endsection
