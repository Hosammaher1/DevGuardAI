<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fix_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('scan_id')->constrained('scans')->cascadeOnDelete();
            $table->foreignUlid('repository_id')->constrained('repositories')->cascadeOnDelete();
            $table->string('base_commit_hash', 64)->nullable();
            $table->string('plan_digest', 64);
            $table->string('title', 255);
            $table->text('summary')->nullable();
            $table->string('risk_level', 32)->default('low');
            $table->integer('fix_credits_required')->default(1);
            $table->string('status', 32)->default('proposed');
            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->integer('pr_number')->nullable();
            $table->string('pr_url', 1024)->nullable();
            $table->timestampsTz();

            $table->index(['workspace_id', 'status']);
            $table->index(['scan_id', 'status']);
        });

        Schema::create('fix_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('fix_plan_id')->constrained('fix_plans')->cascadeOnDelete();
            $table->foreignUlid('test_result_id')->nullable()->constrained('test_results')->nullOnDelete();
            $table->string('target_file', 512);
            $table->text('root_cause')->nullable();
            $table->text('suggested_patch')->nullable();
            $table->string('status', 32)->default('pending');
            $table->text('verification_output')->nullable();
            $table->timestampsTz();

            $table->index(['fix_plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fix_jobs');
        Schema::dropIfExists('fix_plans');
    }
};
