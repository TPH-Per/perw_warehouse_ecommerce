<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory;
use App\Models\Warehouse;

class TestInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test inventory query for debugging';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing inventory query...');

        // Get a sample warehouse
        $warehouse = Warehouse::first();
        if (!$warehouse) {
            $this->error('No warehouses found in database');
            return 1;
        }

        $this->info("Using warehouse: {$warehouse->name} (ID: {$warehouse->id})");

        // Test the query
        $inventories = Inventory::with(['productVariant.product'])
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity_on_hand', '>', 0)
            ->limit(5)
            ->get();

        $this->info("Found {$inventories->count()} inventory records");

        foreach ($inventories as $inventory) {
            $this->line("Inventory ID: {$inventory->id}");
            $this->line("  Product Variant: " . ($inventory->productVariant ? 'Exists' : 'NULL'));
            if ($inventory->productVariant) {
                $this->line("    Variant ID: {$inventory->productVariant->id}");
                $this->line("    Product: " . ($inventory->productVariant->product ? 'Exists' : 'NULL'));
                if ($inventory->productVariant->product) {
                    $this->line("      Product Name: {$inventory->productVariant->product->name}");
                }
            }
            $this->line("  Quantity on hand: {$inventory->quantity_on_hand}");
            $this->line("  Quantity reserved: {$inventory->quantity_reserved}");
            $this->line("");
        }

        return 0;
    }
}
