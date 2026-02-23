<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Status: B = Register/baru, P = Perpanjangan, R = dari register (is_ref true).
     */
    public function up(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->string('status', 1)
                ->nullable()
                ->after('is_ref')
                ->comment('B=Register/baru, P=Perpanjangan, R=Register is_ref true');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
