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
        Schema::create('invoices', function (Blueprint $table)
        {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->longText('customer_address')->nullable();
            $table->timestamp('date')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->integer("tax")->nullable();
            $table->decimal("tax_amount", 15)->nullable();
            $table->integer("discount_percentage")->nullable();
            $table->decimal("discount_percentage_amount", 15)->nullable();
            $table->decimal("discount_amount", 15)->nullable();
            $table->string("referral_code")->nullable();
            $table->decimal("admin_fee", 15)->nullable();
            $table->decimal("service_fee", 15)->nullable();
            $table->decimal("subtotal", 15)->nullable();
            $table->decimal("total", 15)->nullable();
            $table->integer("is_status")->nullable()->comment("0 draft, 1 confirm, 2 success, 3 error");
            $table->integer("is_paid")->nullable()->comment("0 unpaid, 1 paid");

            $table->timestamp('payment_date')->nullable();
            $table->string("payment_method")->nullable();
            $table->decimal("payment_total", 15)->nullable();

            $table->uuid('company_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('cascade');

            $table->index('id', 'invoices_id_index', 'hash');
            $table->index('code', 'invoices_code_index');
            $table->index('customer_id', 'invoices_customer_id_index', 'hash');
            $table->index('is_status', 'invoices_is_status_index', 'hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
