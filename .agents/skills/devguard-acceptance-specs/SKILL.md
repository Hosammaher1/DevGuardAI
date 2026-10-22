---
name: devguard-acceptance-specs
description: Use when defining or reviewing acceptance tests, Gherkin scenarios, negative cases, and requirements traceability for DevGuard AI before implementing software.
---

# DevGuard Acceptance Specs

## Purpose
Specify observable behavior before implementation. Requirements come from the approved DevGuard PRD v4.3 and PD-001–PD-042; this skill does not supersede that specification.

**REQUIRED PROCESS:** Use `superpowers:brainstorming` when behavior is ambiguous. When authorized to implement later, use `superpowers:test-driven-development`.

## Procedure
1. Identify relevant PD requirements and supported release scope; distinguish approved values from proposals.
2. State preconditions, actor, permissions, fixtures and environment, then one observable `Given / When / Then` behavior per case.
3. Include success, denial, abuse, timeout, retry, parallelism, partial coverage and cleanup cases appropriate to the feature.
4. Assign P0 for authorization, tenant isolation, secrets, sandbox escapes, approval bypass and billing integrity.
5. For each case write: `id`, `requirement_ids`, `platform`, `release`, `priority`, `given`, `when`, `then`, `test_level`, `automation_target`, `status`.
6. Review contradictions, missing boundary cases and PD traceability before approving the suite.
7. Status begins `SPECIFIED / NOT EXECUTED`. Never report PASS before running tests and examining results.

## Acceptance invariants
- AI recommendations grant no privileges. Laravel policy and approved scope control actions.
- Repository code always executes in an authorized isolated runner.
- Production tests are non-destructive; mutations need explicitly authorized isolated environments.
- No auto-merge, no automatic overages; unverified review PRs require separate approval.
- Unsupported platform or unavailable runner is BLOCKED/UNSUPPORTED, never PASS.
- Scan credits and Fix credits are distinct, atomic and idempotent.
- Browser evidence, secrets, tenant data and repository artifacts are private and scoped.

## Example
**Case:** `SCN-QUOTA-001` — Two simultaneous jobs compete for one remaining Scan credit.
**Given:** An authorized workspace has one remaining credit, no matching deduplicated job and two unique valid start requests.
**When:** Both requests arrive concurrently.
**Then:** Exactly one reservation succeeds; the other receives a quota-exhausted state; at most one runner starts; no negative balance occurs.
**Test level:** Laravel integration + concurrency.
**Status:** SPECIFIED / NOT EXECUTED.

## Common mistakes
- Combining unrelated assertions into one case: split into single behaviors.
- Treating a proposed price, runner, role permission or retention period as approved: flag for decision.
- Mock-only tests that never exercise authorization or quota persistence: require integration evidence later.
- Treating tests in the specification as executed tests: maintain separate status.
