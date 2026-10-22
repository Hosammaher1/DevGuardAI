<?php

namespace App\Domain\Audit;

use App\Domain\Identity\Role;
use App\Domain\Identity\WorkspaceAction;
use App\Models\AuditEvent;
use InvalidArgumentException;

final class AuthorizationAudit
{
    private const REQUIRED = [
        'actor_identifier',
        'actor_type',
        'action',
        'allowed',
        'reason',
        'policy_version',
        'correlation_id',
        'originating_service',
    ];

    private const OPTIONAL = [
        'workspace_id',
        'resource_identifier',
        'role_before',
        'role_after',
    ];

    private const REASONS = [
        'allowed',
        'matrix_denied',
        'action_unknown',
        'unauthenticated',
        'membership_missing',
        'membership_inactive',
        'resource_scope_denied',
        'role_change_unauthorized',
        'owner_change_requires_transfer',
    ];

    /** @param array<string, mixed> $attributes */
    public function record(array $attributes): AuditEvent
    {
        foreach (self::REQUIRED as $required) {
            if (! array_key_exists($required, $attributes)) {
                throw new InvalidArgumentException("Missing audit field: {$required}");
            }
        }

        $event = array_intersect_key(
            $attributes,
            array_flip([...self::REQUIRED, ...self::OPTIONAL]),
        );
        $event['action'] = $this->actionValue($event['action']);
        $event['role_before'] = $this->roleValue($event['role_before'] ?? null);
        $event['role_after'] = $this->roleValue($event['role_after'] ?? null);
        $event['reason'] = in_array($event['reason'], self::REASONS, true)
            ? $event['reason']
            : 'reason_unclassified';

        return AuditEvent::query()->create($event);
    }

    private function actionValue(mixed $action): string
    {
        if ($action instanceof WorkspaceAction) {
            return $action->value;
        }

        if (is_string($action) && WorkspaceAction::tryFrom($action) !== null) {
            return $action;
        }

        return 'unknown_action';
    }

    private function roleValue(mixed $role): ?string
    {
        if ($role instanceof Role) {
            return $role->value;
        }

        if (is_string($role) && Role::tryFrom($role) !== null) {
            return $role;
        }

        return null;
    }
}
