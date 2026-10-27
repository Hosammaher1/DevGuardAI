<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('repository_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('credit_reservation_id')->nullable()->references('id')->on('credit_reservations')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->string('branch', 128)->default('main');
            $table->string('commit_hash', 64)->nullable();
            $table->string('trigger', 32)->default('manual');
            $table->integer('total_tests')->default(0);
            $table->integer('passed_tests')->default(0);
            $table->integer('failed_tests')->default(0);
            $table->integer('skipped_tests')->default(0);
            $table->integer('duration_ms')->nullable();
            $table->text('summary')->nullable();
            $table->longText('logs')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();

            $table->index(['workspace_id', 'status']);
            $table->index(['repository_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('test_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('scan_id')->constrained('scans')->cascadeOnDelete();
            $table->string('test_name', 512);
            $table->string('test_suite', 255)->nullable();
            $table->string('file_path', 512);
            $table->string('status', 32);
            $table->integer('duration_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();
            $table->string('screenshot_path', 1024)->nullable();
            $table->foreignUlid('screenshot_artifact_id')->nullable()->references('id')->on('artifacts')->nullOnDelete();
            $table->json('ai_analysis')->nullable();
            $table->timestampsTz();

            $table->index(['scan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
        Schema::dropIfExists('scans');
    }
};
