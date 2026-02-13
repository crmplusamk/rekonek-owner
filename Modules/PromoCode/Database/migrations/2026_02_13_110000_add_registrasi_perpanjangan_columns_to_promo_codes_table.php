<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add prefixed columns for Registrasi and Perpanjangan value config.
     */
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->enum('discount_type_registrasi', ['percentage', 'nominal'])->nullable()->after('max_discount');
            $table->integer('discount_percentage_registrasi')->nullable()->after('discount_type_registrasi');
            $table->decimal('discount_amount_registrasi', 15, 2)->nullable()->after('discount_percentage_registrasi');
            $table->decimal('min_purchase_registrasi', 15, 2)->nullable()->after('discount_amount_registrasi');
            $table->decimal('max_discount_registrasi', 15, 2)->nullable()->after('min_purchase_registrasi');

            $table->enum('discount_type_perpanjangan', ['percentage', 'nominal'])->nullable()->after('max_discount_registrasi');
            $table->integer('discount_percentage_perpanjangan')->nullable()->after('discount_type_perpanjangan');
            $table->decimal('discount_amount_perpanjangan', 15, 2)->nullable()->after('discount_percentage_perpanjangan');
            $table->decimal('min_purchase_perpanjangan', 15, 2)->nullable()->after('discount_amount_perpanjangan');
            $table->decimal('max_discount_perpanjangan', 15, 2)->nullable()->after('min_purchase_perpanjangan');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type_registrasi',
                'discount_percentage_registrasi',
                'discount_amount_registrasi',
                'min_purchase_registrasi',
                'max_discount_registrasi',
                'discount_type_perpanjangan',
                'discount_percentage_perpanjangan',
                'discount_amount_perpanjangan',
                'min_purchase_perpanjangan',
                'max_discount_perpanjangan',
            ]);
        });
    }
};
