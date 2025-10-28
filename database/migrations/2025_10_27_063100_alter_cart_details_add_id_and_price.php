<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Ensure referencing columns remain indexed when dropping the composite primary key
        Schema::table('cart_details', function (Blueprint $table) {
            // These indexes are needed so that existing foreign keys (to carts/product_variants)
            // still have supporting indexes after dropping PRIMARY
            $table->index('cart_id', 'cart_details_cart_id_index');
            $table->index('product_variant_id', 'cart_details_variant_id_index');
        });

        // 2) Drop composite primary key (cart_id, product_variant_id)
        Schema::table('cart_details', function (Blueprint $table) {
            $table->dropPrimary(); // drops PRIMARY
        });

        // 3) Add id (auto-increment) as new primary key and add price column
        Schema::table('cart_details', function (Blueprint $table) {
            // Add auto-incrementing primary key id
            $table->bigIncrements('id')->first();

            // Add price column to store price at time of adding to cart
            if (!Schema::hasColumn('cart_details', 'price')) {
                $table->decimal('price', 10, 2)->after('quantity');
            }
        });

        // 4) Ensure uniqueness of (cart_id, product_variant_id)
        Schema::table('cart_details', function (Blueprint $table) {
            $table->unique(['cart_id', 'product_variant_id'], 'cart_details_cart_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) Drop unique index
        Schema::table('cart_details', function (Blueprint $table) {
            $table->dropUnique('cart_details_cart_variant_unique');
        });

        // 2) Drop id and price columns, then restore composite primary key
        Schema::table('cart_details', function (Blueprint $table) {
            // Dropping the id column will also drop its PRIMARY constraint
            if (Schema::hasColumn('cart_details', 'id')) {
                $table->dropColumn('id');
            }
            if (Schema::hasColumn('cart_details', 'price')) {
                $table->dropColumn('price');
            }
        });

        Schema::table('cart_details', function (Blueprint $table) {
            $table->primary(['cart_id', 'product_variant_id']);
        });
    }
};
