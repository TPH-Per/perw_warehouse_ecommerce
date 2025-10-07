<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'role_id' => 2, // Mặc định là End User
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']), // Laravel sẽ tự hash
            'phone_number' => $data['phone_number'] ?? null,
        ]);

        // Tạo token cho người dùng mới đăng ký
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // Cần điều chỉnh để sử dụng cột password_hash
        // Laravel Auth mặc định dùng cột 'password'. Nếu bạn dùng 'password_hash',
        // bạn cần override method getAuthPassword() trong Model User như đã ví dụ ở trên.
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Kiểm tra xem tài khoản có bị cấm hoặc chưa kích hoạt không
            if ($user->status !== 'active') {
                Auth::logout(); // Đăng xuất nếu trạng thái không hợp lệ
                return response()->json(['message' => 'Your account is not active.'], 403);
            }

            // Tạo token mới
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user->load('role', 'addresses'), // Tải thêm thông tin cần thiết
                'token' => $token
            ]);
        }

        return response()->json(['message' => 'Invalid login credentials.'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete(); // Xóa token hiện tại
        return response()->json(['message' => 'Logged out successfully.']);
    }
}