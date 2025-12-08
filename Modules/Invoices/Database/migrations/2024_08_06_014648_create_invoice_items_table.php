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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id')->nullable();
            $table->uuid("itemable_id");
            $table->string("itemable_type");
            $table->bigInteger("duration")->nullable();
            $table->string("duration_type")->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->bigInteger("quantity")->nullable();
            $table->bigInteger("charge")->nullable();
            $table->decimal("capital_price", 15)->nullable();
            $table->decimal("price", 15)->nullable();
            $table->decimal("subtotal", 15)->nullable();

            /** additional data */
            $table->bigInteger("additional_duration")->nullable();
            $table->string("additional_duration_type")->nullable();
            $table->bigInteger("additional_charge")->nullable();
            $table->decimal("additional_total", 15)->nullable();

            $table->timestamps();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
