<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'Carts';

    protected $fillable = [
        'user_id',
    ];

    /**
     * Lấy người dùng sở hữu giỏ hàng.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lấy chi tiết các sản phẩm trong giỏ hàng.
     */
    public function details()
    {
        return $this->hasMany(CartDetail::class, 'cart_id');
    }
}