<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('message');
            $table->string('type', 20)->default('info');
            $table->string('status', 20)->default('draft');
            $table->string('action_label')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_at');
            $table->index('end_at');
            $table->index(['status', 'start_at']);
        });

        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('announcement_id');
            $table->string('target_type', 20);
            $table->uuid('target_id')->nullable();
            $table->string('target_value')->nullable();
            $table->timestamps();

            $table->foreign('announcement_id')
                ->references('id')
                ->on('announcements')
                ->cascadeOnDelete();

            $table->index('announcement_id');
            $table->index('target_type');
            $table->index('target_id');
            $table->index('target_value');
            $table->unique(['announcement_id', 'target_type', 'target_id', 'target_value'], 'announcement_targets_unique_target');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
        Schema::dropIfExists('announcements');
    }
};
