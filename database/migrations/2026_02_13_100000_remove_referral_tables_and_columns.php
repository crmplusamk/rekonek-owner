<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Removes all referral-related tables and columns from backoffice.
     */
    public function up(): void
    {
        if (Schema::hasTable('contacts') && Schema::hasColumn('contacts', 'referral_code')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE contacts DROP CONSTRAINT IF EXISTS contacts_referral_code_foreign');
            } else {
                Schema::table('contacts', function (Blueprint $table) {
                    $table->dropForeign(['referral_code']);
                });
            }
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropIndex('referral_code_index');
                $table->dropColumn('referral_code');
            });
        }

        Schema::dropIfExists('referral_usages');
        Schema::dropIfExists('referrals');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['referral_code']);
                $table->dropColumn('referral_code');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'referral_code')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
