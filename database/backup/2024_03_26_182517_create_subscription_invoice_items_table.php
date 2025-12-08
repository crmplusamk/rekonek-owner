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
        Schema::create('subscription_invoice_items', function (Blueprint $table) {
            $table->uuid("subscription_invoice_id");
            $table->uuid("itemable_id");
            $table->string("itemable_type");
            $table->decimal("total", 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoice_items');
    }
};
