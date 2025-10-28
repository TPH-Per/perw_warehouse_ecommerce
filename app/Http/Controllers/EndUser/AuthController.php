<?php

namespace App\Http\Controllers\EndUser;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('enduser.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user && $user->role && !in_array($user->role->name, ['Customer', 'End User'])) {
                Auth::logout();
                return back()->withErrors(['email' => 'Chỉ tài khoản khách hàng mới được phép đăng nhập khu vực này.']);
            }
            if ($user && $user->status !== 'active') {
                Auth::logout();
                return back()->withErrors(['email' => 'Tài khoản chưa hoạt động. Vui lòng liên hệ hỗ trợ.']);
            }

            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'Thông tin đăng nhập không hợp lệ.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('enduser.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $role = Role::whereIn('name', ['Customer', 'End User'])->orderByRaw("FIELD(name,'Customer','End User')")->first();
        if (!$role) {
            return back()->withErrors(['name' => 'Thiếu role Customer/End User. Vui lòng seed dữ liệu.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'full_name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone_number' => $data['phone_number'] ?? null,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('success', 'Đăng ký thành công!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

