<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('key')->nullable();
            $table->bigInteger('order')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_parent')->nullable();
            $table->boolean('is_addon')->nullable();
            $table->timestamps();
        });

        Schema::table('features', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('features')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
