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
        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promo_code_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('contact_id')->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->decimal('purchase_amount', 15, 2)->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onDelete('cascade');
            $table->index('promo_code_id');
            $table->index('customer_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
    }
};
