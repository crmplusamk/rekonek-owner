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
            $table->uuid("id")->primary();
            $table->uuid("invoice_id")->nullable();
            $table->string('order_id')->nullable();
            $table->uuid("snap_token")->nullable();
            $table->timestamp("date")->nullable();
            $table->timestamp("due_date")->nullable();
            $table->timestamp("paid_date")->nullable();
            $table->string("method")->nullable();
            $table->decimal("total", 15)->nullable();
            $table->integer("is_status")->nullable()->comment("0 draft, 1 confirm, 2 success, 3 error");
            $table->longText("note")->nullable();
            $table->timestamps();
            $table->longText("metadata")->nullable();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');

            $table->index("invoice_id", "invoice_id_index", "hash");
            $table->index("date", "date_index");
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
