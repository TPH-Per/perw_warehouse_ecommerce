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
            // 1. Add a standard auto-incrementing primary key 'id'
            $table->id(); 

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('shipping_method_id');
            $table->string('tracking_code', 100)->nullable();
            $table->string('tracking_number', 100)->nullable()->guid(); // Note: ->guid() might not be the correct method here, check Laravel docs if you get an error.
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'returned', 'failed'])->default('pending');
            $table->timestamps();
            $table->string('carrier')->nullable();
            $table->date('shipped_at')->nullable();
            $table->string('delivered_at')->nullable(); // Note: This should probably be a date/datetime type, not string.

            // 2. REMOVE: $table->primary('order_id');

            $table->foreign('order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods');
            
            // Optional: If you still want to ensure one shipment per order, 
            // you can add a UNIQUE index instead of a PRIMARY key:
            // $table->unique('order_id'); 
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