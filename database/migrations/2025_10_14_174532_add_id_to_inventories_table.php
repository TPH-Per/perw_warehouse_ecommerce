<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite requires recreating the table
            // Store existing data
            $inventories = DB::table('inventories')->get();
            
            // Drop the table and recreate with new structure
            Schema::dropIfExists('inventories');
            
            Schema::create('inventories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_variant_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->integer('quantity_on_hand')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('reorder_level')->default(0);
                $table->timestamps();
                $table->softDeletes();
                
                $table->unique(['product_variant_id', 'warehouse_id']);
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            });
            
            // Restore data if any
            foreach ($inventories as $inventory) {
                DB::table('inventories')->insert([
                    'product_variant_id' => $inventory->product_variant_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'quantity_on_hand' => $inventory->quantity_on_hand,
                    'quantity_reserved' => $inventory->quantity_reserved,
                    'reorder_level' => $inventory->reorder_level ?? 0,
                    'created_at' => $inventory->created_at,
                    'updated_at' => $inventory->updated_at,
                ]);
            }
        } else {
            DB::statement('ALTER TABLE `inventories` DROP PRIMARY KEY, ADD `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST, ADD UNIQUE KEY `inventories_product_variant_id_warehouse_id_unique` (`product_variant_id`, `warehouse_id`)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // Store existing data
            $inventories = DB::table('inventories')->get();
            
            // Drop and recreate with original structure
            Schema::dropIfExists('inventories');
            
            Schema::create('inventories', function (Blueprint $table) {
                $table->unsignedBigInteger('product_variant_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->integer('quantity_on_hand')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('reorder_level')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->primary(['product_variant_id', 'warehouse_id']);
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            });
            
            // Restore data
            foreach ($inventories as $inventory) {
                DB::table('inventories')->insert([
                    'product_variant_id' => $inventory->product_variant_id,
                    'warehouse_id' => $inventory->warehouse_id,
                    'quantity_on_hand' => $inventory->quantity_on_hand,
                    'quantity_reserved' => $inventory->quantity_reserved,
                    'reorder_level' => $inventory->reorder_level ?? 0,
                    'created_at' => $inventory->created_at,
                    'updated_at' => $inventory->updated_at,
                ]);
            }
        } else {
            DB::statement('ALTER TABLE `inventories` DROP PRIMARY KEY, DROP KEY `inventories_product_variant_id_warehouse_id_unique`, MODIFY `id` BIGINT UNSIGNED, ADD PRIMARY KEY (`product_variant_id`, `warehouse_id`)');

            Schema::table('inventories', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }
    }
};
