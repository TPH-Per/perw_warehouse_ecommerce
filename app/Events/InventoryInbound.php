<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryInbound
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  int  $warehouseId
     * @param  int  $productVariantId
     * @param  int  $quantity
     * @param  string|null  $notes
     * @param  int  $transactionId
     * @return void
     */
    public function __construct(
        public int $warehouseId,
        public int $productVariantId,
        public int $quantity,
        public ?string $notes,
        public int $transactionId
    ) {
        //
    }
}
