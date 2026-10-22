<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artifacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('artifact_type', 32);
            $table->string('original_name', 255);
            $table->string('storage_key', 128)->unique();
            $table->char('ciphertext_sha256', 64);
            $table->unsignedBigInteger('plaintext_bytes');
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['workspace_id', 'artifact_type', 'expires_at']);
        });

        Schema::create('artifact_access_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('artifact_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('credential_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('account_key', 128)->unique();
            $table->longText('encrypted_secret');
            $table->unsignedBigInteger('version')->default(1);
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('credential_leases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('credential_account_id')->constrained()->cascadeOnDelete();
            $table->string('job_identifier', 128);
            $table->string('worker_identifier', 128);
            $table->string('target_origin', 512);
            $table->char('token_hash', 64)->unique();
            $table->unsignedBigInteger('credential_version');
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('redeemed_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['credential_account_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credential_leases');
        Schema::dropIfExists('credential_accounts');
        Schema::dropIfExists('artifact_access_links');
        Schema::dropIfExists('artifacts');
    }
};
