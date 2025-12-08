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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id')->nullable();
            $table->uuid('subscription_id')->nullable();
            $table->string('code')->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->string('tax')->nullable();
            $table->string('tax_amount')->nullable();
            $table->string('discount')->nullable();
            $table->string('total')->nullable();
            $table->string('status')->nullable();
            $table->uuid('company_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
