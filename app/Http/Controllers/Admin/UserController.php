<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Admin\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Lấy tất cả người dùng, loại trừ Admin và những người đã bị xóa mềm
        $users = User::where('id', '!=', auth()->id()) // Không hiển thị chính admin đang đăng nhập
                     ->where('role_id', '!=', 1) // Không hiển thị các Admin khác (nếu có)
                     ->whereNull('deleted_at') // Chỉ lấy những người chưa bị xóa mềm
                     ->with('role') // Tải kèm thông tin role
                     ->get();

        return response()->json($users);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::where('id', $id)
                    ->where('id', '!=', auth()->id())
                    ->where('role_id', '!=', 1)
                    ->whereNull('deleted_at')
                    ->with('role', 'addresses')
                    ->firstOrFail(); // Trả về 404 nếu không tìm thấy

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $user = User::where('id', $id)
                    ->where('id', '!=', auth()->id())
                    ->where('role_id', '!=', 1)
                    ->whereNull('deleted_at')
                    ->firstOrFail();

        $data = $request->validated();

        // Cập nhật các trường có thể thay đổi
        $user->full_name = $data['full_name'] ?? $user->full_name;
        $user->email = $data['email'] ?? $user->email; // Cần validation email unique
        $user->phone_number = $data['phone_number'] ?? $user->phone_number;
        $user->status = $data['status'] ?? $user->status;
        // Có thể thêm logic đổi mật khẩu nếu cần

        $user->save();

        return response()->json(['message' => 'User updated successfully.', 'user' => $user->load('role')]);
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(string $id)
    {
        $user = User::where('id', $id)
                    ->where('id', '!=', auth()->id())
                    ->where('role_id', '!=', 1)
                    ->whereNull('deleted_at')
                    ->firstOrFail();

        $user->delete(); // Thực hiện soft delete

        return response()->json(['message' => 'User deleted successfully.']);
    }
}