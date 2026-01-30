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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('transaction_code')->unique();
            $table->decimal('total_before_discount', 10, 2);
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('total_after_discount', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->date('transaction_date');
            $table->enum('payment_method', ['cash', 'qris', 'transfer']);
            $table->enum('status', ['pending', 'paid', 'unpaid', 'cancelled']);
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
