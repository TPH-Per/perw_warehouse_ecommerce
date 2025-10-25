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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Make fields nullable to support direct sales (walk-in, no shipping)
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('shipping_recipient_name', 100)->nullable()->change();
            $table->string('shipping_recipient_phone', 20)->nullable()->change();
            $table->text('shipping_address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Revert to NOT NULL (note: will fail if existing nulls present)
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->string('shipping_recipient_name', 100)->nullable(false)->change();
            $table->string('shipping_recipient_phone', 20)->nullable(false)->change();
            $table->text('shipping_address')->nullable(false)->change();
        });
    }
};

