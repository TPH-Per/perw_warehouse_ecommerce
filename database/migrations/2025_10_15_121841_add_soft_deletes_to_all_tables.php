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
        // Add soft deletes to all tables
        Schema::table('addresses', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('cart_details', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove soft deletes from all tables
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('cart_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
