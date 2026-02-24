<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menyimpan promo yang dipakai di checkout agar usage bisa dicatat saat invoice lunas.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('promo_code_id')->nullable()->after('company_id');
            $table->string('promo_usage_status', 1)->nullable()->after('promo_code_id')
                ->comment('B=Register/baru, P=Perpanjangan; dipakai saat record usage saat lunas');

            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'promo_usage_status']);
        });
    }
};
