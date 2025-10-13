<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes; // Cần cột `deleted_at` trong DB

    protected $table = 'Warehouses';

    protected $fillable = [
        'name',
        'location',
    ];

    /**
     * Lấy tất cả bản ghi tồn kho trong kho này.
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }
}