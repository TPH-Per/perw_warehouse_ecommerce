@extends('enduser.layout')

@section('title','Đơn hàng của tôi')

@section('content')
  @section('breadcrumbs')
    @include('enduser.partials.breadcrumbs', ['items' => [
      ['label' => 'Trang chủ', 'url' => route('enduser.home')],
      ['label' => 'Đơn hàng của tôi']
    ]])
  @endsection
  <h2 class="mb-3">Đơn hàng của tôi</h2>
  @if ($orders->isEmpty())
    <p>Bạn chưa có đơn hàng nào.</p>
  @else
    <div class="table-responsive bg-white rounded shadow-sm">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Mã đơn</th>
          <th>Ngày tạo</th>
          <th>Trạng thái</th>
          <th>Tổng tiền</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($orders as $o)
          <tr>
            <td>{{ $o->order_code }}</td>
            <td>{{ $o->created_at }}</td>
            <td>
              @php
                $badge = 'secondary';
                if ($o->status==='pending') $badge='warning';
                elseif ($o->status==='confirmed') $badge='info';
                elseif ($o->status==='shipped') $badge='primary';
                elseif ($o->status==='delivered') $badge='success';
                elseif ($o->status==='cancelled') $badge='danger';
              @endphp
              <span class="badge bg-{{ $badge }} badge-status">{{ $o->status }}</span>
            </td>
            <td>{{ number_format($o->total_amount,0,',','.') }}đ</td>
            <td>
              <a href="{{ route('enduser.order.show', $o->id) }}" class="btn btn-sm btn-outline-secondary">Chi tiết</a>
              @if (in_array($o->status, ['pending','confirmed']))
                <form method="post" action="{{ route('enduser.order.cancel', $o->id) }}" class="d-inline-block ms-1">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">Hủy</button></form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    {{ $orders->links('vendor.pagination.bootstrap-5') }}
  @endif
@endsection
