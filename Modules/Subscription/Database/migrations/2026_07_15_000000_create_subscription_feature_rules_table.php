<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot aturan fitur bawaan paket per-subscription (grandfathering).
     * Dibekukan saat subscription dibuat (subscribe/renew/upgrade/downgrade); addon tetap live.
     */
    public function up(): void
    {
        Schema::create('subscription_feature_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->uuid('feature_id');
            $table->uuid('company_id')->nullable();
            $table->string('limit')->nullable();       // nilai beku ("-1" = unlimited)
            $table->string('limit_type')->nullable();
            $table->boolean('included')->nullable();
            $table->boolean('visiblity')->nullable();   // ikut ejaan existing package_feature
            $table->string('source')->default('package'); // 'package' | 'manual' | 'admin_push'
            $table->timestamps();

            $table->unique(['subscription_id', 'feature_id']);
            $table->index('company_id');
            $table->index('feature_id');

            $table->foreign('subscription_id')->references('id')->on('subscription_packages')->onDelete('cascade');
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_feature_rules');
    }
};
