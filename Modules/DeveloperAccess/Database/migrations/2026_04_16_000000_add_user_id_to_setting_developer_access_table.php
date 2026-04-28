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
        Schema::table('setting_developer_access', function (Blueprint $table) {
            $table->uuid("user_id")->nullable()->after("company_name");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_developer_access', function (Blueprint $table) {
            $table->dropColumn("user_id");
        });
    }
};
