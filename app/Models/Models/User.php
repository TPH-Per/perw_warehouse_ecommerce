<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Tên bảng tương ứng (nếu khác quy tắc số nhiều)
    protected $table = 'Users';

    // Các cột có thể gán hàng loạt (mass assignment)
    protected $fillable = [
        'role_id',
        'full_name',
        'email',
        'password_hash', // Lưu ý: Laravel Auth sẽ tự hash, bạn có thể cần điều chỉnh
        'phone_number',
        'avatar_url',
        'status',
    ];

    // Các cột bị ẩn khi serialize (ví dụ: password)
    protected $hidden = [
        'password_hash', // Đổi tên nếu bạn dùng password_hash thay vì password
        'remember_token',
    ];

    // Các cột kiểu boolean
    protected $casts = [
        'is_deleted' => 'boolean',
        'is_default' => 'boolean',
        'is_approved' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Sử dụng Soft Deletes
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $dates = ['deleted_at']; // Cột deleted_at sẽ được thêm tự động nếu chưa có

    // Quan hệ với Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Quan hệ với Addresses
    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    // Quan hệ với Cart
    public function cart()
    {
        return $this->hasOne(Cart::class, 'user_id');
    }

    // Quan hệ với PurchaseOrders
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'user_id');
    }

    // Helper method để kiểm tra quyền Admin
    public function isAdmin()
    {
        return $this->role_id === 1; // Giả định role_id 1 là Admin
    }

    // Cần override method password để Laravel Auth hoạt động với cột password_hash
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}