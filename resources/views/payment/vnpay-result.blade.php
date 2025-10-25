<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán VNPAY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { max-width: 560px; margin: 8vh auto; }
    </style>
    </head>
<body>
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h3 class="mb-3">Kết quả thanh toán VNPAY</h3>
            @if($success)
                <div class="alert alert-success">{{ $message }}</div>
            @else
                <div class="alert alert-danger">{{ $message }}</div>
            @endif

            @if(isset($order))
                <p class="text-muted mb-4">Mã đơn hàng: <strong>{{ $order->order_code }}</strong></p>
                <a class="btn btn-primary" href="{{ url('/') }}">Về trang chủ</a>
                <a class="btn btn-outline-secondary" href="{{ url('/manager/sales/'.$order->id) }}">Xem đơn (Manager)</a>
            @else
                <a class="btn btn-primary" href="{{ url('/') }}">Về trang chủ</a>
            @endif
        </div>
    </div>
</body>
</html>

