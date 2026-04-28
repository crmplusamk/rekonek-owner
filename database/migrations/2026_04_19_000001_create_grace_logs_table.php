<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel grace_logs: tracking tiap touchpoint drip campaign yang terkirim
     * selama grace period subscription.
     *
     * - grace_started_at berperan ganda sebagai cycle identifier: jika user
     *   expired → grace → renew → expired lagi → grace lagi, tiap cycle punya
     *   tanggal berbeda sehingga log lama tidak konflik dengan cycle baru.
     * - UNIQUE per (company_id, grace_started_at, touchpoint_key, channel) menjamin
     *   idempotency: jika command jalan 2x di hari yang sama, insert kedua fail
     *   secara natural tanpa double-send.
     */
    public function up(): void
    {
        Schema::create('grace_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('subscription_package_id')->nullable();
            $table->date('grace_started_at');
            $table->string('touchpoint_key', 10)->comment('H+1, H+3, H+7, H+10, ... H+31');
            $table->string('channel', 10)->comment('wa | email');
            $table->string('status', 20)->default('queued')->comment('queued | sent | failed');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'grace_started_at', 'touchpoint_key', 'channel'],
                'uniq_grace_log_per_channel'
            );

            $table->index('company_id', 'idx_grace_logs_company');
            $table->index('subscription_package_id', 'idx_grace_logs_sub_pkg');
            $table->index(['status', 'created_at'], 'idx_grace_logs_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grace_logs');
    }
};
