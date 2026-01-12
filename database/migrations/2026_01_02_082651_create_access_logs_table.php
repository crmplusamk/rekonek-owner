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
        Schema::create('access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('category')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('email')->nullable();
            $table->string('number')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('method')->nullable();
            $table->string('endpoint')->nullable();
            $table->integer('status_code')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('response_time')->nullable()->comment('Response time in milliseconds');
            $table->string('progress')->nullable();
            $table->string('session_id')->nullable();
            $table->string('device_type')->nullable();
            $table->string('platform')->nullable();
            $table->string('action')->nullable();
            $table->string('activity_type')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('company_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};

