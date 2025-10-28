<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_details', function (Blueprint $table) {
            // Drop the previous unique index on (cart_id, product_variant_id)
            // added in 2025_10_27_063100_alter_cart_details_add_id_and_price
            try {
                $table->dropUnique('cart_details_cart_variant_unique');
            } catch (\Throwable $e) {
                // ignore if index name differs or already dropped
            }

            // Add a new unique index that includes deleted_at to allow re-adding after soft delete
            $table->unique(['cart_id', 'product_variant_id', 'deleted_at'], 'cart_details_cart_variant_deleted_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_details', function (Blueprint $table) {
            try {
                $table->dropUnique('cart_details_cart_variant_deleted_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            // Restore the simpler unique (may fail if existed before)
            try {
                $table->unique(['cart_id', 'product_variant_id'], 'cart_details_cart_variant_unique');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};

