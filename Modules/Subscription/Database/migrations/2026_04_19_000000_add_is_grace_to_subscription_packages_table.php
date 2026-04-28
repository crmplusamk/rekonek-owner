<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom is_grace untuk menandakan status grace period subscription:
     * - 'active'    : subscription normal (baik trial maupun subs yang belum expired)
     * - 'grace'     : sudah expired, dalam masa 30 hari grace period (drip campaign berjalan)
     * - 'end_grace' : grace habis (30 hari setelah expired_at), siap untuk data deletion
     *
     * Kolom grace_started_at digunakan sebagai cycle identifier:
     * - NULL saat is_grace = 'active'
     * - Di-set ke tanggal saat is_grace transisi ke 'grace' (biasanya expired_at + 1)
     * - Tetap tersimpan saat is_grace = 'end_grace' (untuk audit)
     *
     * Catatan: Data existing dengan is_active=false dan expired_at<today TIDAK di-backfill
     * otomatis ke 'grace'. Mereka tetap 'active' (legacy expired). Jika perlu backfill ke
     * 'end_grace' secara manual, gunakan SQL terpisah.
     */
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->string('is_grace', 20)
                ->default('active')
                ->after('is_trial')
                ->comment('Grace period state: active | grace | end_grace');

            $table->date('grace_started_at')
                ->nullable()
                ->after('is_grace')
                ->comment('Tanggal grace period dimulai, juga cycle identifier di grace_logs');

            $table->index(['is_grace', 'expired_at'], 'idx_subs_pkg_grace_expired');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropIndex('idx_subs_pkg_grace_expired');
            $table->dropColumn(['is_grace', 'grace_started_at']);
        });
    }
};
