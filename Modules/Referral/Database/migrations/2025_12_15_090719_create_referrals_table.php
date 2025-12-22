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
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->uuid('user_id')->nullable(); // User yang membuat referral code
            $table->enum('discount_type', ['percentage', 'nominal'])->default('percentage');
            $table->integer('discount_percentage')->nullable();
            $table->decimal('discount_amount', 15, 2)->nullable();
            $table->decimal('min_purchase', 15, 2)->nullable();
            $table->decimal('max_discount', 15, 2)->nullable();
            $table->bigInteger('usage_limit')->nullable(); // Total limit penggunaan
            $table->bigInteger('used_count')->default(0);
            $table->bigInteger('per_user_limit')->default(1); // Limit per user/company
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('user_id');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
