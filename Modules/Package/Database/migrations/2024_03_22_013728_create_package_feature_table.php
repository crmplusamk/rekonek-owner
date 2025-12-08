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
        Schema::create('package_feature', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('feature_id');
            $table->string('limit')->nullable();
            $table->string('limit_type')->nullable();
            $table->boolean('included')->nullable();
            $table->boolean('visiblity')->nullable();
            $table->timestamps();

            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_feature');
    }
};
