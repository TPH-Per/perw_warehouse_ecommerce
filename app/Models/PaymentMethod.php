<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    /**
     * Get the payments for the payment method.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_method_id');
    }
}
