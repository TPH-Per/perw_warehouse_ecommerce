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
        Schema::create('shipments', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shipping_method_id');
            $table->string('tracking_code', 100)->nullable();
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'returned'])->default('pending');
            $table->timestamps();

            $table->primary('order_id');
            $table->foreign('order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
