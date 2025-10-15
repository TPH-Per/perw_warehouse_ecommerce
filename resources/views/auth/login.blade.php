<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Bảng điều khiển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #87CEEB 0%, #00BFFF 50%, #1E90FF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }

        /* Animated clouds background */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 60% 30%, rgba(255, 255, 255, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.25) 0%, transparent 50%);
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50%, -50%); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .login-header {
            background: linear-gradient(135deg, #87CEEB 0%, #00BFFF 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .login-header i {
            font-size: 3em;
            margin-bottom: 15px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
        }

        .login-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8em;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #E0F6FF;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #00BFFF;
            box-shadow: 0 0 0 0.2rem rgba(0, 191, 255, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #357ABD;
            margin-bottom: 8px;
        }

        .btn-login {
            background: linear-gradient(135deg, #87CEEB 0%, #00BFFF 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
            width: 100%;
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 191, 255, 0.4);
            background: linear-gradient(135deg, #00BFFF 0%, #1E90FF 100%);
        }

        .form-check-input:checked {
            background-color: #00BFFF;
            border-color: #00BFFF;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .alert-danger {
            background-color: #ffe6e6;
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-cloud-sun-fill"></i>
                <h4>PerW Admin</h4>
                <p>Đăng nhập để tiếp tục</p>
            </div>
            <div class="login-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i> Địa chỉ Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" required autofocus
                               placeholder="Nhập email của bạn">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock"></i> Mật khẩu
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               required placeholder="Nhập mật khẩu của bạn">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                    </button>
                </form>

                <div class="text-center mt-3">
                    <small class="text-muted">Mặc định: admin@perw.com / password</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
