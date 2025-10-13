<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'Notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'action_url',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Notification types
    const TYPE_ORDER_CREATED = 'order_created';
    const TYPE_ORDER_UPDATED = 'order_updated';
    const TYPE_ORDER_SHIPPED = 'order_shipped';
    const TYPE_ORDER_DELIVERED = 'order_delivered';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_LOW_STOCK = 'low_stock';
    const TYPE_PRODUCT_BACK_IN_STOCK = 'product_back_in_stock';
    const TYPE_REVIEW_POSTED = 'review_posted';
    const TYPE_SYSTEM = 'system';

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Query Scopes
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            return $this->update(['read_at' => now()]);
        }
        return true;
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Static helper methods
     */
    public static function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $actionUrl = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
        ]);
    }

    public static function markAllAsRead(int $userId): int
    {
        return self::where('user_id', $userId)
                  ->whereNull('read_at')
                  ->update(['read_at' => now()]);
    }

    public static function getUnreadCount(int $userId): int
    {
        return self::where('user_id', $userId)
                  ->whereNull('read_at')
                  ->count();
    }

    public static function deleteOldNotifications(int $days = 90): int
    {
        return self::where('created_at', '<', now()->subDays($days))
                  ->where('read_at', '!=', null)
                  ->delete();
    }

    /**
     * Get icon class based on notification type
     */
    public function getIconClass(): string
    {
        return match($this->type) {
            self::TYPE_ORDER_CREATED, self::TYPE_ORDER_UPDATED => '📦',
            self::TYPE_ORDER_SHIPPED => '🚚',
            self::TYPE_ORDER_DELIVERED => '✅',
            self::TYPE_PAYMENT_RECEIVED => '💰',
            self::TYPE_LOW_STOCK => '⚠️',
            self::TYPE_PRODUCT_BACK_IN_STOCK => '🔔',
            self::TYPE_REVIEW_POSTED => '⭐',
            self::TYPE_SYSTEM => 'ℹ️',
            default => '📢',
        };
    }

    /**
     * Get badge class based on notification type
     */
    public function getBadgeClass(): string
    {
        return match($this->type) {
            self::TYPE_ORDER_CREATED, self::TYPE_ORDER_UPDATED => 'bg-blue-100 text-blue-800',
            self::TYPE_ORDER_SHIPPED => 'bg-indigo-100 text-indigo-800',
            self::TYPE_ORDER_DELIVERED => 'bg-green-100 text-green-800',
            self::TYPE_PAYMENT_RECEIVED => 'bg-green-100 text-green-800',
            self::TYPE_LOW_STOCK => 'bg-red-100 text-red-800',
            self::TYPE_PRODUCT_BACK_IN_STOCK => 'bg-purple-100 text-purple-800',
            self::TYPE_REVIEW_POSTED => 'bg-yellow-100 text-yellow-800',
            self::TYPE_SYSTEM => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
