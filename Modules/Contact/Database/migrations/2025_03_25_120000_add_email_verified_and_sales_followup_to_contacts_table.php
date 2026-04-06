<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('verification_code');
            $table->timestamp('sales_registration_followup_sent_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['email_verified_at', 'sales_registration_followup_sent_at']);
        });
    }
};
