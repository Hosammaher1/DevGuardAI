# DevGuard AI T01 Identity/RBAC Technical Contract

**Status:** Final technical contract for the dependency-ready Identity/RBAC slice under the product-owner authorization dated 2026-10-22. This contract implements PRD v4.3 PD-008/009/010/013/039, approved ND01, SG-01/02/07/08, Blueprint B01/BA-01, and T02. It does not alter product policy or resolve commercial decisions.

## 1. Versioned implementation baseline

| Component | Exact version | Contract |
|---|---:|---|
| PHP CLI/runtime | 8.2.12 | Local development baseline; production remains PHP 8.2-compatible until an independently validated upgrade. |
| Composer | 2.9.7 | Dependency resolution; `composer.lock` is authoritative and must be committed with application changes. |
| Laravel application skeleton | 12.12.2 | Minimal API/control-plane application under `backend/`; no password or alternate social-login scaffold. |
| Laravel framework | 12.70.1 | Pin this exact initial version; upgrades require relevant regression and security review. |
| Laravel Socialite | 5.31.0 | GitHub user authentication only; repository GitHub App credentials never authenticate users. |
| PHPUnit | 11.5.57 | Deterministic PHP test runner compatible with PHP 8.2. |
| PostgreSQL | 18.6 | Local authoritative persistence and concurrency test baseline on the isolated `devguard_test` database. Production provider selection remains unresolved. |

No Node dependency is required for this backend slice. Local deterministic tests use SQLite only where database-engine concurrency is irrelevant. Authorization, invitation replay, ownership races and persistence constraints require PostgreSQL integration evidence before their acceptance cases can be marked complete; the validated local baseline is PostgreSQL 18.6.

## 2. Module and interface boundary

Implementation lives under `backend/`:

- `app/Domain/Identity/Role.php` — closed enum: Owner, Admin, Member, Viewer.
- `app/Domain/Identity/WorkspaceAction.php` — closed enum containing exactly the 17 ND01 matrix actions.
- `app/Domain/Identity/AuthorizationDecision.php` — immutable allow/deny result with a stable non-secret reason code.
- `app/Domain/Identity/AuthorizationService.php` — evaluates a current active membership and authorized resource scope; default deny.
- `app/Domain/Identity/ExecutionScope.php` — later immutable server-issued workspace/resource/SHA/action binding; caller claims never establish authority.
- `app/Domain/Identity/GitHubIdentity.php` and the OAuth controller — later stateful Socialite callback/session boundary.
- `app/Domain/Identity/MembershipService.php` — later invitation, role-change and ownership-transfer transaction boundary.
- `app/Domain/Audit/AuthorizationAudit.php` — later allowlisted security-audit writer.

The first independently safe child slice implements only `Role`, `WorkspaceAction`, `AuthorizationDecision`, and the pure ND01 matrix evaluator. It owns DG-IAM-014 and DG-IAM-022 at unit level; those cases remain incomplete until their named Laravel integration surfaces exist.

## 3. Persistence and concurrency invariants

PostgreSQL is the authoritative persistence engine. UUID/ULID identifiers are server-issued and treated as opaque.

- `users.github_user_id` is non-null and globally unique. Mutable email and display name never establish or link identity.
- `workspaces` have one or more active Owners. A personal workspace follows the same authorization rules as a Team workspace.
- `memberships` are unique on `(workspace_id, user_id)` and contain only the four approved roles plus an active/revoked state and monotonic version.
- Every tenant-owned resource has a trusted `workspace_id` relationship. Mixed-workspace parent/child references are rejected in application policy and constrained in the physical schema where possible.
- Invitations store a random-token hash, intended stable GitHub identity, workspace, permitted Member/Viewer role, expiry, revocation, acceptance identity and acceptance time. Raw tokens are never persisted. Acceptance is single-use and transactional.
- Ownership transfers bind workspace, current Owner, intended new Owner, confirmation and acceptance evidence. Completion locks the workspace and relevant memberships, revalidates both actors, promotes the accepted Owner, and only then permits removal/demotion of a prior Owner.
- Membership removal, role reduction and sensitive actions use transactions with locked current membership rows. Dispatch/action boundaries still re-read current authorization; a snapshot or queue payload is never permanent authority.
- Invitation acceptance and ownership/member mutations use bounded retry on serialization/deadlock errors. PostgreSQL integration tests must force interleavings and prove uniqueness, single use and last-Owner preservation.
- Audit events are append-only application records. Corrections append a linked event; ordinary application paths do not update or delete prior audit events.

## 4. Authentication and session contract

- Browser login uses stateful Laravel Socialite with the `github` driver. OAuth `state` is mandatory; callback errors, cancellation, invalid/expired code and provider failure create no session.
- Account lookup uses GitHub's stable provider user ID. Email changes or reuse cannot link accounts. A conflicting provider ID is denied and audited.
- OAuth access/refresh tokens are not persisted for user login. Repository access uses the separately authorized GitHub App boundary in T05.
- Successful login rotates the session identifier and creates the appropriate personal workspace transactionally if absent. Login does not imply repository access.
- Sessions use the Laravel database driver, encrypted transport in deployed environments, `HttpOnly`, `Secure`, and `SameSite=Lax`; state-changing browser routes require CSRF protection.
- Logout calls the authentication guard, invalidates the session and regenerates the CSRF token. Expired or revoked sessions cannot authorize APIs or worker commands.
- API and worker authorization consume a trusted authenticated actor/service identity and current server-issued scope. Submitted role/workspace fields, AI text and GitHub App tokens are untrusted data.

## 5. Authorization and audit contract

The evaluator accepts a validated active membership, a closed `WorkspaceAction`, and a resource-scope result. It returns an immutable decision. Missing membership, inactive membership, scope mismatch, unknown action or unknown role denies by default. A matrix allow never bypasses GitHub consent, approval, sandbox, quota, billing or kill-switch policy.

Sensitive execution boundaries must revalidate current membership and relevant independent controls immediately before sandbox dispatch, AI Fix execution, GitHub writes and billing effects. Fix approval binding remains owned by T14/T15.

Audit serialization is allowlist-only: event ID/time, actor ID/type, workspace/resource opaque IDs, action, allow/deny, stable reason, role before/after when applicable, policy version, correlation ID and originating trusted service. It excludes OAuth/App tokens, session/cookie/CSRF values, invitation tokens, secrets, request bodies, repository contents, patches and arbitrary exception text. Secret-bearing inputs use fixed redacted markers; audit reads are tenant-scoped and separately authorized.

## 6. Synthetic fixtures and deterministic commands

Fixtures use generated opaque IDs and reserved addresses/domains only. They contain two isolated workspaces, all four roles, active/revoked memberships, every matrix action, cross-tenant resource IDs, synthetic GitHub IDs, mutable duplicate emails, invitation hashes/states, ownership-transfer states and sentinel strings such as `DG_SECRET_SENTINEL_NEVER_LOG`.

Required commands from `backend/`:

```powershell
php artisan test --testsuite=Unit --filter=AuthorizationMatrixTest
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature --filter=Identity
php artisan test --testsuite=Feature
vendor\bin\pint.bat --test
composer audit --locked --no-interaction
```

PostgreSQL-only concurrency tests use an explicitly configured local disposable database and a separate `Integration` suite. Absence of that environment blocks concurrency evidence; SQLite results must never be reported as proof of row locking or serializable behavior.

## 7. Child-slice acceptance order

1. Closed role/action types, exact ND01 matrix and default denial — DG-IAM-014, DG-IAM-022.
2. Current membership/resource scope and tenant denial — DG-IAM-001–006, DG-IAM-008, DG-IAM-015–016.
3. Socialite identity and session lifecycle — DG-IAM-007, DG-IAM-009–013.
4. Invitation and ownership transactions — DG-IAM-017–020.
5. Protected audit records and boundary integration — DG-IAM-021 plus later T08/T14/T15/T16/T19 regressions.

Each child slice follows RED, minimum GREEN, refactor, focused regression and security review. An individual unit result does not complete T02 or change the master catalog's `SPECIFIED / NOT EXECUTED` status.
