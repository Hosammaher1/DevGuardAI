<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->restrictOnDelete();
            $table->string('credit_kind', 8);
            $table->unsignedBigInteger('available_credits')->default(0);
            $table->unsignedBigInteger('reserved_credits')->default(0);
            $table->unsignedBigInteger('consumed_credits')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            $table->unique(['workspace_id', 'credit_kind']);
            $table->unique(['id', 'workspace_id']);
            $table->unique(['id', 'workspace_id', 'credit_kind']);
            $table->index(['workspace_id', 'credit_kind', 'available_credits']);
        });

        Schema::create('credit_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->restrictOnDelete();
            $table->ulid('credit_account_id');
            $table->string('credit_kind', 8);
            $table->foreignId('initiating_user_id')->constrained('users')->restrictOnDelete();
            $table->string('logical_operation_id', 128);
            $table->char('idempotency_hash', 64);
            $table->char('request_fingerprint', 64);
            $table->unsignedBigInteger('amount');
            $table->string('state', 24);
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['workspace_id', 'idempotency_hash']);
            $table->unique(['workspace_id', 'credit_kind', 'logical_operation_id']);
            $table->unique(['id', 'workspace_id']);
            $table->foreign(['credit_account_id', 'workspace_id', 'credit_kind'])
                ->references(['id', 'workspace_id', 'credit_kind'])
                ->on('credit_accounts')
                ->restrictOnDelete();
            $table->index(['workspace_id', 'state', 'created_at']);
        });

        Schema::create('credit_ledger_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('workspace_id')->constrained()->restrictOnDelete();
            $table->ulid('credit_account_id');
            $table->ulid('credit_reservation_id')->nullable();
            $table->foreignId('initiating_user_id')->constrained('users')->restrictOnDelete();
            $table->string('entry_type', 24);
            $table->char('idempotency_hash', 64);
            $table->char('request_fingerprint', 64);
            $table->unsignedBigInteger('amount');
            $table->bigInteger('available_delta');
            $table->bigInteger('reserved_delta');
            $table->bigInteger('consumed_delta');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['workspace_id', 'idempotency_hash']);
            $table->foreign(['credit_account_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])
                ->on('credit_accounts')
                ->restrictOnDelete();
            $table->foreign(['credit_reservation_id', 'workspace_id'])
                ->references(['id', 'workspace_id'])
                ->on('credit_reservations')
                ->restrictOnDelete();
            $table->index(['credit_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_ledger_entries');
        Schema::dropIfExists('credit_reservations');
        Schema::dropIfExists('credit_accounts');
    }
};
