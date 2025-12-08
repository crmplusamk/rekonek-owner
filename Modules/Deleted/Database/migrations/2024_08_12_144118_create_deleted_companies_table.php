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
        Schema::create('deleted_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contact_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('is_status')->nullable();
            $table->longText('reason')->nullable();
            $table->longText('note')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp("request_date")->nullable();
            $table->timestamp("deleted_date")->nullable();
            $table->timestamp("deleted_by")->nullable();
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');

            $table->index('contact_id', 'deleted_companies_contact_id_index', 'hash');
            $table->index('company_name', 'deleted_companies_company_name_index');
            $table->index('email', 'deleted_companies_email_index');
            $table->index('phone', 'deleted_companies_phone_index');
            $table->index('is_status', 'deleted_companies_is_status_index', 'hash');
            $table->index('request_date', 'deleted_companies_request_date_index');
            $table->index('deleted_date', 'deleted_companies_deleted_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_companies');
    }
};
