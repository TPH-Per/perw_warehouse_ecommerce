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
        // First, drop the existing primary key and add the id column
        DB::statement('ALTER TABLE `inventories` DROP PRIMARY KEY, ADD `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST, ADD UNIQUE KEY `inventories_product_variant_id_warehouse_id_unique` (`product_variant_id`, `warehouse_id`)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `inventories` DROP PRIMARY KEY, DROP KEY `inventories_product_variant_id_warehouse_id_unique`, MODIFY `id` BIGINT UNSIGNED, ADD PRIMARY KEY (`product_variant_id`, `warehouse_id`)');

        // Drop the id column
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
