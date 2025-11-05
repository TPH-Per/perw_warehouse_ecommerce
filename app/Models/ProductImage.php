<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'image_url',
        'is_primary',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
        'is_primary' => 'boolean',
    ];

    /**
     * Ensure image_url always returns a properly formatted URL.
     */
    public function getImageUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // If it's already a full URL, return as is
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        // If it's already a proper relative path starting with /storage/, return as is
        if (Str::startsWith($value, '/storage/')) {
            return $value;
        }

        // For any other relative path, ensure it's in the storage directory
        return '/storage/' . ltrim($value, '/');
    }

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
