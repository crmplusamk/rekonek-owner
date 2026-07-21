<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flag tipe billing addon (menggantikan hardcode feature.key === 'AICRD' di banyak tempat):
 *   - 'recurring' : addon berulang co-terminous dgn paket (MAU, Nomor WA, CS Agent). Harga prorata
 *                   sisa cycle, di-rebill saat renewal, kapasitas DIPERTAHANKAN saat perpanjangan.
 *   - 'onetime'   : addon sekali beli / prepaid (AI Credit). Harga penuh (tanpa prorata), saldo
 *                   carry-over lintas cycle, akumulasi (topup), reset saat lapse penuh.
 *
 * Flag ini mengatur perilaku BILLING. Identitas fitur AI Credit untuk metering/entitlement pool
 * (addon_credit) tetap memakai feature.key = 'AICRD' (ranah terpisah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            $table->string('billing_type')->default('recurring')->after('price');
        });

        // Set addon AI Credit (fitur key AICRD) menjadi 'onetime'.
        DB::table('addons')
            ->whereIn('feature_id', function ($q) {
                $q->select('id')->from('features')->where('key', 'AICRD');
            })
            ->update(['billing_type' => 'onetime']);
    }

    public function down(): void
    {
        Schema::table('addons', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
