<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseMonitor extends Model
{
    use HasFactory;

    protected $table = 'WarehouseMonitors';

    // Bảng này không có cột updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id',
        'temperature',
        'humidity',
        'timestamp',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    /**
     * Lấy kho hàng được giám sát.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}