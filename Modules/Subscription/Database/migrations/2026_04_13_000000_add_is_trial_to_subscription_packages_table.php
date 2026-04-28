<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom is_trial untuk menandakan status subscription:
     * - 'trial' : subscription dalam masa percobaan (Business trial 14 hari)
     * - 'subs'  : subscription berbayar reguler (setelah pembayaran sukses)
     *
     * Catatan: Data existing harus di-update manual setelah migration dijalankan.
     *   - Subscription paket Free → is_trial = 'trial'
     *   - Subscription paket berbayar → is_trial = 'subs'
     */
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            // Default 'trial': semua subscription baru dimulai sebagai trial
            // Berubah ke 'subs' setelah pembayaran sukses via Midtrans webhook
            $table->string('is_trial')
                ->default('trial')
                ->comment('Status subscription: "trial" untuk masa percobaan, "subs" untuk subscription berbayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn('is_trial');
        });
    }
};
