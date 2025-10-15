<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'cost',
        'is_active',
    ];

    /**
     * Get the shipments for the shipping method.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'shipping_method_id');
    }
}
