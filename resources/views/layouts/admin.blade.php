<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Bảng điều khiển Admin') - PerW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sky-blue: #87CEEB;
            --deep-sky: #00BFFF;
            --light-sky: #E0F6FF;
            --cloud-white: #F8FBFF;
            --sky-gradient: linear-gradient(135deg, #87CEEB 0%, #00BFFF 100%);
            --sidebar-gradient: linear-gradient(180deg, #4A90E2 0%, #357ABD 100%);
        }

        body {
            background: linear-gradient(135deg, #E0F6FF 0%, #B8E6FF 50%, #87CEEB 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: var(--sidebar-gradient);
            min-height: 100vh;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .sidebar-header {
            padding: 25px 20px;
            color: white;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .sidebar-header h3 {
            font-weight: bold;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        main {
            padding: 30px;
        }

        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border-left: 5px solid var(--deep-sky);
        }

        .page-header h1 {
            color: #357ABD;
            margin: 0;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid var(--deep-sky);
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: var(--sky-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .stat-card h5 {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2.2em;
            font-weight: bold;
            color: #357ABD;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .card-header {
            background: var(--sky-gradient);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--sky-gradient);
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 191, 255, 0.4);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead {
            background: var(--sky-gradient);
            color: white;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: var(--light-sky);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            margin: 10px;
            color: white;
        }

        .logout-btn {
            color: rgba(255, 255, 255, 0.9);
            padding: 10px 20px;
            margin: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <div class="sidebar-header">
                    <h3><i class="bi bi-cloud-sun"></i> PerW</h3>
                    <small>Bảng điều khiển Admin</small>
                </div>
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                               href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Bảng điều khiển
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                               href="{{ route('admin.products.index') }}">
                                <i class="bi bi-box-seam"></i> Sản phẩm
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                               href="{{ route('admin.orders.index') }}">
                                <i class="bi bi-cart-check"></i> Đơn hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}"
                               href="{{ route('admin.inventory.index') }}">
                                <i class="bi bi-boxes"></i> Kho hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                               href="{{ route('admin.users.index') }}">
                                <i class="bi bi-people"></i> Người dùng
                            </a>
                        </li>
                    </ul>

                    @auth
                    <div class="user-info">
                        <small>Đăng nhập với tư cách</small>
                        <div><strong>{{ auth()->user()->full_name }}</strong></div>
                        <div><small>{{ auth()->user()->email }}</small></div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn logout-btn w-100">
                            <i class="bi bi-box-arrow-right"></i> Đăng xuất
                        </button>
                    </form>
                    @endauth
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
