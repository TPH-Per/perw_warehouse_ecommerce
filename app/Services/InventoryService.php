<?php

namespace App\Services;

use App\Events\InventoryInbound;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Handle inbound receipts for a product variant in a warehouse.
     *
     * @param  int|string      $warehouseId Warehouse ID (numeric) or any resolvable identifier.
     * @param  int|string      $variantId   Variant ID or SKU code that can be resolved to an ID.
     * @param  int             $quantity    Quantity received; must be > 0.
     * @param  string|null     $notes       Optional note describing the inbound source.
     * @return array{
     *     transaction: array{id:int, created_at:\Illuminate\Support\Carbon, type:string},
     *     inventory: array{
     *         quantity_on_hand:int,
     *         quantity_reserved:int,
     *         available_quantity:int
     *     }
     * }
     */
    public function inbound(int|string $warehouseId, int|string $variantId, int $quantity, ?string $notes = null): array
    {
        if (is_array($warehouseId)) {
            throw new InvalidArgumentException('Warehouse ID must be an integer or string, array given. Use inboundBatch() for multiple items.');
        }

        if (is_array($variantId)) {
            throw new InvalidArgumentException('Variant ID must be an integer or string, array given. Use inboundBatch() for multiple items.');
        }

        // Basic guard: quantity must be positive.
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Inbound quantity must be greater than zero.');
        }

        // Resolve variant identifier if caller supplied a SKU.
        $resolvedVariantId = $this->resolveVariantId($variantId);
        if (!$resolvedVariantId) {
            throw new InvalidArgumentException('Unable to resolve product variant from the given identifier.');
        }

        $result = DB::transaction(function () use ($warehouseId, $resolvedVariantId, $quantity, $notes): array {
            return $this->processInbound($warehouseId, $resolvedVariantId, $quantity, $notes);
        });

        // Register after-commit hooks for the processed item.
        $this->queuePostInboundHooks($warehouseId, $resolvedVariantId, $quantity, $notes, $result);

        return $result;
    }

    /**
     * Handle inbound for many items in one warehouse.
     *
     * @param array<int,array{variant_id?:int, sku?:string, quantity:int, notes?:string|null}> $items
     */
    public function inboundBatch(int|string $warehouseId, array $items, ?string $notes = null): array
    {
        if (is_array($warehouseId)) {
            throw new InvalidArgumentException('Warehouse ID must be an integer or string, array given.');
        }

        if (count($items) === 0) {
            throw new InvalidArgumentException('Batch inbound requires at least one item.');
        }

        return DB::transaction(function () use ($warehouseId, $items, $notes): array {
            $results = [];

            foreach ($items as $index => $item) {
                $variantIdentifier = $item['variant_id'] ?? $item['sku'] ?? null;
                $quantity = $item['quantity'] ?? 0;
                $itemNotes = $item['notes'] ?? $notes;

                if (!$variantIdentifier) {
                    throw new InvalidArgumentException("Item {$index}: missing variant identifier.");
                }

                if ($quantity <= 0) {
                    throw new InvalidArgumentException("Item {$index}: inbound quantity must be greater than zero.");
                }

                $resolvedVariantId = $this->resolveVariantId($variantIdentifier);
                if (!$resolvedVariantId) {
                    throw new InvalidArgumentException("Item {$index}: unable to resolve product variant.");
                }

                $results[] = $this->processInbound($warehouseId, $resolvedVariantId, $quantity, $itemNotes);

                // Fire hooks per item after the transaction commits.
                $this->queuePostInboundHooks($warehouseId, $resolvedVariantId, $quantity, $itemNotes, $results[array_key_last($results)]);
            }

            return $results;
        });
    }

    /**
     * Resolve variant identifier to a numeric ID.
     */
    protected function resolveVariantId(int|string $variantId): ?int
    {
        // Already numeric → assume it is the primary key.
        if (is_int($variantId) || ctype_digit((string) $variantId)) {
            return (int) $variantId;
        }

        // Otherwise treat it as a SKU or custom code.
        return ProductVariant::query()
            ->where('sku', $variantId)
            ->value('id');
    }

    /**
     * Execute the core inbound logic. Should be called inside an open DB transaction.
     */
    protected function processInbound(int|string $warehouseId, int $variantId, int $quantity, ?string $notes = null): array
    {
        $inventory = Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if (!$inventory) {
            $inventory = Inventory::create([
                'warehouse_id'       => $warehouseId,
                'product_variant_id' => $variantId,
                'quantity_on_hand'   => 0,
                'quantity_reserved'  => 0,
                'reorder_level'      => config('inventory.default_reorder_level', 0),
            ]);
        }

        $inventory->quantity_on_hand += $quantity;
        if ($inventory->quantity_on_hand < 0) {
            throw new InvalidArgumentException('On-hand quantity cannot be negative after inbound processing.');
        }
        $inventory->save();

        $transaction = InventoryTransaction::create([
            'product_variant_id' => $variantId,
            'warehouse_id'       => $warehouseId,
            'type'               => 'inbound',
            'quantity'           => $quantity,
            'notes'              => $notes,
        ]);

        return [
            'transaction' => [
                'id'         => $transaction->id,
                'created_at' => $transaction->created_at,
                'type'       => $transaction->type,
                'product_variant_id' => $transaction->product_variant_id,
                'warehouse_id'       => $transaction->warehouse_id,
                'quantity'           => $transaction->quantity,
            ],
            'inventory' => [
                'product_variant_id' => $inventory->product_variant_id,
                'warehouse_id'       => $inventory->warehouse_id,
                'quantity_on_hand'   => $inventory->quantity_on_hand,
                'quantity_reserved'  => $inventory->quantity_reserved,
                'available_quantity' => $inventory->quantity_on_hand - $inventory->quantity_reserved,
            ],
        ];
    }

    /**
     * Register after-commit hooks for a processed inbound item.
     */
    protected function queuePostInboundHooks(int|string $warehouseId, int $variantId, int $quantity, ?string $notes, array $result): void
    {
        DB::afterCommit(function () use ($warehouseId, $variantId, $quantity, $notes, $result): void {
            Log::info('Inventory inbound recorded.', [
                'warehouse_id'     => $warehouseId,
                'product_variant_id' => $variantId,
                'quantity'         => $quantity,
                'notes'            => $notes,
                'transaction_id'   => $result['transaction']['id'],
                'performed_by'     => Auth::id(),
            ]);

            event(new InventoryInbound(
                $warehouseId,
                $variantId,
                $quantity,
                $notes,
                $result['transaction']['id']
            ));

            // app(PreorderFulfillmentService::class)->attemptAutoFulfill($variantId);
        });
    }
}
