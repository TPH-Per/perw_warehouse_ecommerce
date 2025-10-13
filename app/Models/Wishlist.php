<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Wishlist extends Model
{
    use HasFactory;

    protected $table = 'Wishlists';

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Query Scopes
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithProductDetails(Builder $query): Builder
    {
        return $query->with(['product.images', 'product.variants', 'product.category']);
    }

    /**
     * Helper Methods
     */
    public static function toggleWishlist(int $userId, int $productId): array
    {
        $wishlist = self::where('user_id', $userId)
                       ->where('product_id', $productId)
                       ->first();

        if ($wishlist) {
            $wishlist->delete();
            return [
                'action' => 'removed',
                'message' => 'Product removed from wishlist',
                'in_wishlist' => false,
            ];
        } else {
            self::create([
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
            return [
                'action' => 'added',
                'message' => 'Product added to wishlist',
                'in_wishlist' => true,
            ];
        }
    }

    public static function isInWishlist(int $userId, int $productId): bool
    {
        return self::where('user_id', $userId)
                  ->where('product_id', $productId)
                  ->exists();
    }
}
