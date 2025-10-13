<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes; // Cần cột `deleted_at` trong DB

    protected $table = 'Addresses';

    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_phone',
        'street_address',
        'ward',
        'district',
        'city',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Lấy người dùng sở hữu địa chỉ này.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}