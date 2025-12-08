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
        Schema::create('subscription_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->uuid('addon_id')->nullable();
            $table->bigInteger('charge')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->boolean('is_active')->nullable();
            $table->uuid('company_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('addon_id')->references('id')->on('addons')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_addons');
    }
};
