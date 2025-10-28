<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title','Wibu Shop')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    :root {
        --brand: #e91e63;
    }

    .navbar-brand {
        font-weight: 700;
        letter-spacing: .3px;
    }

    .product-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: .5rem;
    }

    .price {
        color: #dc3545;
        font-weight: 700;
    }

    .badge-status {
        text-transform: capitalize
    }

    .card-hover {
        transition: transform .2s, box-shadow .2s
    }

    .card-hover:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .08)
    }

    .sticky-aside {
        position: sticky;
        top: 1rem
    }
    </style>
    @stack('head')
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand text-danger" href="{{ route('enduser.home') }}"><i
                    class="bi bi-bag-heart-fill me-2"></i>Wibu Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span
                    class="navbar-toggler-icon"></span></button>
            <div id="mainNav" class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="{{ route('enduser.home') }}">Trang chủ</a></li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="{{ route('enduser.cart') }}"><i class="bi bi-cart3 me-1"></i>Giỏ hàng
                            <!-- @if(!empty($enduserCartCount))
                <span class="badge bg-danger ms-1">{{ $enduserCartCount }}</span>
              @endif -->
                        </a>
                    </li>
                    @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><i
                                class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('enduser.orders') }}"><i
                                        class="bi bi-receipt me-2"></i>Đơn hàng của tôi</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="post" action="{{ route('enduser.logout') }}" class="px-3 py-1">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i
                                            class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li class="nav-item"><a class="btn btn-outline-danger ms-lg-3"
                            href="{{ route('enduser.login') }}">Đăng nhập</a></li>
                    <li class="nav-item"><a class="btn btn-danger ms-lg-2" href="{{ route('enduser.register') }}">Đăng
                            ký</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4">
        @yield('breadcrumbs')

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
        @endif

        @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-top py-4 mt-5">
        <div class="container small text-muted d-flex justify-content-between">
            <span>© {{ date('Y') }} Wibu Shop</span>
            <span><i class="bi bi-shield-lock me-1"></i>Thanh toán bảo mật</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-hide alerts after a while for smoother UX
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(a => setTimeout(() => a.classList.add('d-none'), 4000));
    </script>
    @stack('scripts')
</body>

</html>