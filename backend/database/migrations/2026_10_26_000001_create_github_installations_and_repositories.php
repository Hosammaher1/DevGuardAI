<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_app_installations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('installation_id');
            $table->string('account_login', 255);
            $table->string('account_type', 64)->default('Organization');
            $table->string('repository_selection', 32)->default('selected');
            $table->json('permissions')->nullable();
            $table->foreignId('installed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->unique(['workspace_id', 'installation_id']);
            $table->index(['installation_id', 'status']);
        });

        Schema::create('repositories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('github_app_installation_id')->constrained('github_app_installations')->cascadeOnDelete();
            $table->unsignedBigInteger('github_repo_id');
            $table->string('name', 255);
            $table->string('full_name', 512);
            $table->string('owner_login', 255);
            $table->boolean('is_private')->default(false);
            $table->string('default_branch', 128)->default('main');
            $table->string('status', 32)->default('connected');
            $table->boolean('auto_scan_enabled')->default(false);
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['workspace_id', 'github_repo_id']);
            $table->index(['workspace_id', 'status']);
            $table->index(['full_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repositories');
        Schema::dropIfExists('github_app_installations');
    }
};
