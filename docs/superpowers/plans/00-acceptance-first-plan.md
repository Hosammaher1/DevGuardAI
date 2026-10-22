# DevGuard AI — Acceptance-First Planning (Superpowers format)

> Planning only; do not scaffold, code, run tests or install dependencies without explicit user authorization.

**Goal:** Produce separate, complete master test suite for all PRD v4.3 capabilities before the engineering implementation plan.

**Source of truth:** PD-001–PD-042 (v4.3). Proposed numeric/technical parameters remain unapproved.

## Task 1 — Requirements traceability
- [ ] Enumerate all PD-001–PD-042 clauses, P0/P1/P2 priority, and planned launch R1–R5.
- [ ] Cross-check approved vs proposed quota, permission and runner limits.
- [ ] Review mapping with product owner.

## Task 2 — Universal Test Catalog
- [ ] Write suites: Auth, RBAC, GitHub, Project Discovery, Scan/Quota, Web/API/Full-Stack, AI Evals, Mobile/Desktop, Sandbox/SSRF, Reporting, AI Fix, Human Review, Billing, Privacy, Reliability, Notifications and Release Gates.
- [ ] For every case record stable ID, PRD refs, priority, release, Given/When/Then, test layer and NOT EXECUTED status.
- [ ] Add abuse/negative/race/failure/recovery cases, not just happy paths.
- [ ] Mark unavailable runners as UNSUPPORTED; do not claim they passed.

## Task 3 — Specification review
- [ ] Review tenant access and approvals independently.
- [ ] Review secrets, sandbox/SSRF and billing reconciliation independently.
- [ ] Remove duplicates, identify gaps and unresolved assumptions.
- [ ] Sign off test specification before implementation plans.

## Task 4 — Engineering design handoff
- [ ] Make approved subsystem design specifications.
- [ ] Turn approved tests into small test-first implementation plan tasks.
- [ ] Require explicit authorization before execution.

## Future execution discipline (not authorized)
- [ ] Write one failing automated test (RED); prove expected failure.
- [ ] Implement minimum behavior (GREEN); run targeted and full suite.
- [ ] Refactor, review, verify and commit evidence.
