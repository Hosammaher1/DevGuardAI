<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_inbox', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('provider', 32)->default('github');
            $table->string('delivery_id', 128);
            $table->string('event_type', 64);
            $table->string('payload_hash', 64);
            $table->string('status', 32)->default('received');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'delivery_id']);
            $table->index(['event_type', 'status']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('repository_id')->constrained('repositories')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('default_branch', 128)->default('main');
            $table->string('framework', 64)->default('react');
            $table->string('status', 32)->default('active');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->unique(['workspace_id', 'repository_id']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('target_authorizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('target_url', 1024);
            $table->string('environment', 32)->default('test');
            $table->string('action_scope', 32)->default('non_destructive');
            $table->string('status', 32)->default('authorized');
            $table->string('verification_token', 64)->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['workspace_id', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_authorizations');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('provider_inbox');
    }
};
