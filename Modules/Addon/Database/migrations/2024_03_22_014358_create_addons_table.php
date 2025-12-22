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
        Schema::create('addons', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("feature_id")->nullable();
            $table->string("name")->nullable();
            $table->longText("description")->nullable();
            $table->bigInteger("quantity")->nullable();
            $table->bigInteger("charge")->nullable();
            $table->decimal('price', 15);
            $table->boolean('is_active')->nullable();
            $table->timestamps();

            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};
