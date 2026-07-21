<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan diskon addon GENERIK (volume tier per-blok), bisa dipakai semua addon.
 *
 * Resolusi: untuk quantity (jumlah blok) tertentu, ambil tier aktif dengan `min_quantity`
 * TERBESAR yang <= quantity. `type` menentukan arti `value`:
 *   - 'unit_price' : harga per blok pada tier ini (mis. AI Credit >=5 blok = 90.000/blok)
 *   - 'percent'    : diskon persen dari harga master (0-100)
 * Bila tak ada tier cocok → harga master `addons.price`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_price_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('addon_id');
            $table->integer('min_quantity');                 // berlaku bila jumlah blok >= nilai ini
            $table->string('type')->default('unit_price');   // 'unit_price' | 'percent'
            $table->decimal('value', 15, 2);                 // unit_price: harga/blok ; percent: % diskon
            $table->string('label')->nullable();             // keterangan tampil, mis. "Diskon"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('addon_id')->references('id')->on('addons')->onDelete('cascade');
            $table->unique(['addon_id', 'min_quantity']);
            $table->index(['addon_id', 'is_active', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_price_tiers');
    }
};
