<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case PAID = 'paid';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING_PAYMENT => 'Pending Payment',
            self::PAID => 'Paid',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING_PAYMENT => 'bg-yellow-100 text-yellow-800',
            self::PAID => 'bg-green-100 text-green-800',
            self::PROCESSING => 'bg-blue-100 text-blue-800',
            self::SHIPPED => 'bg-indigo-100 text-indigo-800',
            self::DELIVERED => 'bg-green-100 text-green-800',
            self::CANCELLED => 'bg-red-100 text-red-800',
            self::REFUNDED => 'bg-purple-100 text-purple-800',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        $allowedTransitions = match($this) {
            self::PENDING_PAYMENT => [self::PAID, self::CANCELLED],
            self::PAID => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED],
            default => [],
        };

        return in_array($status, $allowedTransitions);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
