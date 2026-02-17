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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('discount_id', 'fk_transaction_discount')
                ->references('id')
                ->on('discounts')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->foreign('customer_id', 'fk_transaction_customer')
                ->references('id')
                ->on('customers')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->foreign('transaction_id', 'fk_transaction_detail_transaction')
                ->references('id')
                ->on('transactions')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('service_id', 'fk_transaction_detail_service')
                ->references('id')
                ->on('services')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->foreign('service_group_id', 'fk_service_service_group')
                ->references('id')
                ->on('service_groups')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });


        Schema::table('package_details', function (Blueprint $table) {
            $table->foreign('package_id', 'fk_package_detail_package')
                ->references('id')
                ->on('packages')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreign('service_id', 'fk_package_detail_service')
                ->references('id')
                ->on('services')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
