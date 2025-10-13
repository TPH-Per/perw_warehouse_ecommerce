<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes; // Cần cột `deleted_at` trong DB

    protected $table = 'Suppliers';

    protected $fillable = [
        'name',
        'contact_info',
    ];

    /**
     * Lấy tất cả sản phẩm từ nhà cung cấp này.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }
}