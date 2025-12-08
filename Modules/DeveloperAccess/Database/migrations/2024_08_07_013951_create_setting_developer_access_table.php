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
        Schema::create('setting_developer_access', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("account_name")->nullable();
            $table->string("account_email")->nullable();
            $table->longText("token_access")->nullable();
            $table->string("time_access")->nullable();
            $table->timestamp("start_date")->nullable();
            $table->timestamp("end_date")->nullable();
            $table->longText("note")->nullable();
            $table->uuid("company_id")->nullable();
            $table->longText("company_name")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_developer_access');
    }
};
