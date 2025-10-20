<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the inventory records for the warehouse.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }

    /**
     * Get the inventory transactions for the warehouse.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'warehouse_id');
    }

    /**
     * Get the users assigned to this warehouse.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
