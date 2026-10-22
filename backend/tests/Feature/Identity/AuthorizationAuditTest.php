<?php

namespace Tests\Feature\Identity;

use App\Domain\Audit\AuthorizationAudit;
use App\Domain\Identity\Role;
use App\Domain\Identity\WorkspaceAction;
use App\Models\AuditEvent;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class AuthorizationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_allowlisted_fields_are_persisted_without_secret_context(): void
    {
        $workspace = Workspace::query()->create(['name' => 'Synthetic Audit Workspace']);
        $sentinel = 'DG_SECRET_SENTINEL_NEVER_LOG';

        $event = app(AuthorizationAudit::class)->record([
            'actor_identifier' => 'user:synthetic-1',
            'actor_type' => 'user',
            'workspace_id' => $workspace->getKey(),
            'resource_identifier' => 'project:synthetic-1',
            'action' => WorkspaceAction::GrantOrRevokeAdmin,
            'allowed' => false,
            'reason' => 'matrix_denied',
            'role_before' => Role::Admin,
            'role_after' => Role::Owner,
            'policy_version' => 'nd01-v1',
            'correlation_id' => 'corr-synthetic-1',
            'originating_service' => 'laravel-control-plane',
            'oauth_token' => $sentinel,
            'session_cookie' => $sentinel,
            'request_body' => $sentinel,
            'exception' => $sentinel,
        ]);

        self::assertSame('grant_or_revoke_admin', $event->action);
        self::assertFalse($event->allowed);
        self::assertSame('admin', $event->role_before);
        self::assertSame('owner', $event->role_after);

        $stored = (string) json_encode(
            AuditEvent::query()->findOrFail($event->getKey())->getAttributes(),
            JSON_THROW_ON_ERROR,
        );
        self::assertStringNotContainsString($sentinel, $stored);
        self::assertStringNotContainsString('oauth_token', $stored);
        self::assertStringNotContainsString('request_body', $stored);
    }

    public function test_audit_events_are_append_only_through_the_model_boundary(): void
    {
        $event = app(AuthorizationAudit::class)->record([
            'actor_identifier' => 'user:synthetic-2',
            'actor_type' => 'user',
            'action' => WorkspaceAction::StartScans,
            'allowed' => true,
            'reason' => 'allowed',
            'policy_version' => 'nd01-v1',
            'correlation_id' => 'corr-synthetic-2',
            'originating_service' => 'laravel-control-plane',
        ]);

        $this->expectException(LogicException::class);

        $event->update(['reason' => 'tampered']);
    }
}
