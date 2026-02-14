<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliator_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('commission_type_registrasi', 20)->nullable();
            $table->decimal('commission_value_registrasi', 15, 2)->nullable();
            $table->string('commission_type_perpanjangan', 20)->nullable();
            $table->decimal('commission_value_perpanjangan', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliator_configs');
    }
};
