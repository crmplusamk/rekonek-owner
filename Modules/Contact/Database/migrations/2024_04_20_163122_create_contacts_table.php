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
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('login_email')->nullable();
            $table->string('login_password')->nullable();
            $table->boolean('is_customer')->nullable();
            $table->boolean('is_active')->nullable();
            $table->string('referral_code')->nullable();
            $table->string('verification_code')->nullable();
            $table->timestamps();

            $table->foreign('referral_code')->references('code')->on('referrals')->onDelete('set null');

            $table->index('company_id', 'company_id_index', 'hash');
            $table->index('is_customer', 'is_customer_index', 'hash');
            $table->index('is_active', 'is_active_index', 'hash');
            $table->index('referral_code', 'referral_code_index', 'hash');
            $table->index('verification_code', 'verification_code_index', 'hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
