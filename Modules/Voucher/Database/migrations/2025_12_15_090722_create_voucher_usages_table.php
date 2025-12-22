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
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('voucher_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('order_id')->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->decimal('purchase_amount', 15, 2)->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');
            $table->index('voucher_id');
            $table->index('user_id');
            $table->index('company_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};
