<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'PurchaseOrders';

    protected $fillable = [
        'user_id',
        'order_code',
        'status',
        'shipping_recipient_name',
        'shipping_recipient_phone',
        'shipping_address',
        'sub_total',
        'shipping_fee',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'payment_status',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'sub_total' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Payment status constants
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_PARTIAL = 'partial';
    const PAYMENT_STATUS_REFUNDED = 'refunded';
    const PAYMENT_STATUS_FAILED = 'failed';

    /**
     * Query Scopes
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING_PAYMENT);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeShipped(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('order_code', 'like', "%{$search}%")
              ->orWhere('shipping_recipient_name', 'like', "%{$search}%")
              ->orWhere('shipping_recipient_phone', 'like', "%{$search}%");
        });
    }

    /**
     * Lấy người dùng đã đặt hàng.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lấy chi tiết các sản phẩm trong đơn hàng.
     */
    public function details()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'order_id');
    }

    /**
     * Lấy thông tin vận chuyển của đơn hàng.
     */
    public function shipment()
    {
        return $this->hasOne(Shipment::class, 'order_id');
    }

    /**
     * Lấy các giao dịch thanh toán của đơn hàng.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    /**
     * Get inventory transactions for this order
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'order_id');
    }

    /**
     * Accessors
     */
    public function getItemCountAttribute(): int
    {
        return $this->details->sum('quantity');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()
                    ->where('status', 'completed')
                    ->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->total_paid >= $this->total_amount;
    }

    public function getStatusLabelAttribute(): string
    {
        return str_replace('_', ' ', ucwords($this->status, '_'));
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 0, ',', '.') . ' VND';
    }

    public function getEstimatedDeliveryDateAttribute(): ?string
    {
        if ($this->shipped_at) {
            return $this->shipped_at->addDays(3)->format('Y-m-d');
        }
        return null;
    }

    /**
     * Helper Methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->is_fully_paid;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PAID,
        ]);
    }

    public function canBeShipped(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_PROCESSING,
        ]);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
        ]) && $this->is_fully_paid;
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING_PAYMENT => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PAID => 'bg-green-100 text-green-800',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800',
            self::STATUS_SHIPPED => 'bg-indigo-100 text-indigo-800',
            self::STATUS_DELIVERED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            self::STATUS_REFUNDED => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getProgressPercentage(): int
    {
        return match($this->status) {
            self::STATUS_PENDING_PAYMENT => 10,
            self::STATUS_PAID => 25,
            self::STATUS_PROCESSING => 50,
            self::STATUS_SHIPPED => 75,
            self::STATUS_DELIVERED => 100,
            self::STATUS_CANCELLED, self::STATUS_REFUNDED => 0,
            default => 0,
        };
    }

    /**
     * Get available status transitions
     */
    public function getAvailableStatusTransitions(): array
    {
        return match($this->status) {
            self::STATUS_PENDING_PAYMENT => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
            default => [],
        };
    }
}