<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use HasFactory, SoftDeletes; // Cần cột `deleted_at` trong DB

    protected $table = 'ProductImages';

    protected $fillable = [
        'product_id',
        'image_url',
        'sort_order',
    ];

    /**
     * Lấy sản phẩm sở hữu hình ảnh này.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}