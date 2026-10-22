<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('actor_identifier');
            $table->string('actor_type', 32);
            $table->foreignUlid('workspace_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('resource_identifier')->nullable();
            $table->string('action');
            $table->boolean('allowed');
            $table->string('reason', 64);
            $table->string('role_before', 16)->nullable();
            $table->string('role_after', 16)->nullable();
            $table->string('policy_version', 64);
            $table->string('correlation_id');
            $table->string('originating_service', 64);
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
