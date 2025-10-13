<?php

namespace App\Enums;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DRAFT = 'draft';
    case OUT_OF_STOCK = 'out_of_stock';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DRAFT => 'Draft',
            self::OUT_OF_STOCK => 'Out of Stock',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'bg-green-100 text-green-800',
            self::INACTIVE => 'bg-red-100 text-red-800',
            self::DRAFT => 'bg-yellow-100 text-yellow-800',
            self::OUT_OF_STOCK => 'bg-gray-100 text-gray-800',
        };
    }

    public function isAvailableForPurchase(): bool
    {
        return $this === self::ACTIVE;
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
