<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'Roles';

    // Laravel tự quản lý created_at và updated_at
    // public $timestamps = true; // Mặc định là true

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Lấy tất cả người dùng thuộc vai trò này.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}