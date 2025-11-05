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
        Schema::create('payments', function (Blueprint $table) {
            // 1. Add a standard auto-incrementing primary key 'id'
            $table->id(); 

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_code', 100)->nullable();
            $table->timestamps();

            // 2. REMOVE: $table->primary('order_id');

            $table->foreign('order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods');
            
            // Optional: If you still want to ensure one payment per order, 
            // you can add a UNIQUE index instead of a PRIMARY key:
            // $table->unique('order_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};