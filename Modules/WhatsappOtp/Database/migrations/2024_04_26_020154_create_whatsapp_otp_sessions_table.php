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
        Schema::create('whatsapp_otp_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session')->nullable();
            $table->string('number')->nullable();
            $table->boolean('is_sync')->nullable()->default(false);
            $table->integer('status')->default(0);

            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_otp_sessions');
    }
};
