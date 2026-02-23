<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Untuk idempotensi: satu usage per (promo_code_id, invoice_id) saat invoice lunas.
     */
    public function up(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->uuid('invoice_id')->nullable()->after('company_id');
            $table->unique(['promo_code_id', 'invoice_id'], 'promo_code_usages_promo_invoice_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->dropUnique('promo_code_usages_promo_invoice_unique');
            $table->dropColumn('invoice_id');
        });
    }
};
