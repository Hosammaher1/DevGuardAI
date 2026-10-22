# DevGuard AI — Codex Skill Routing

**Baseline:** PRD v4.3 / PD-001–PD-042. R1 planning artifacts include the Master Test Plan, implementation plan and Engineering Blueprint. The product owner has authorized isolated local orchestrator tooling; this does **not** authorize DevGuard application implementation or R1 task execution.

## Current documentation status
- ND01 Fixed Role Matrix and ND13 Selective Containment are approved product policies and recorded in the PRD and decision register.
- The R1 Engineering Blueprint and Expert Runtime QA planning are documentation artifacts; acceptance cases remain `SPECIFIED / NOT EXECUTED`.
- Local PowerShell orchestrator tooling is separately authorized and implemented under `tooling/local-orchestrator`. Real execution remains hard-blocked until a validated Windows isolation boundary exists for gate commands and the approved task-base policy supports pre-approved sequential tasks; the entry also requires an explicit switch and administrator-protected policy, trusted owner keys, revocations, task approvals and task-specific gates. Synthetic tooling tests do not execute DevGuard application tests or authorize R1 task execution, real approvals, or pushes to the DevGuard remote.
- Commercial registration, payment-provider onboarding, payout arrangements, market activation and launch-specific legal reviews remain deferred until R1 has been built and validated, subject to essential privacy, security, isolation and provider-use controls during development.

## Always use on relevant tasks
- `superpowers:using-superpowers` before task routing.
- `superpowers:brainstorming` for any new subsystem or ambiguity.
- `superpowers:writing-plans` after an approved subsystem specification.
- `superpowers:test-driven-development` only when coding is explicitly authorized.
- `superpowers:verification-before-completion` before claiming tests pass or release readiness.
- `superpowers:writing-skills` before creating/altering a custom Skill.

## Skills status
- `devguard-acceptance-specs`: **draft authored**, YAML/structural checks only; pressure-test and human review required before installation/production reliance.
- Other suggested custom skills: **not authored**; add one by one only if reusable guidance is needed beyond the PRD and AGENTS.md.

## Planned specialist skills (create only when needed)

| Proposed skill | Trigger | Status |
|---|---|---|
| `devguard-laravel-control-plane` | Laravel auth, workspace RBAC, authorization boundaries | Not created |
| `devguard-github-integration` | GitHub OAuth, GitHub App installation, webhook and draft PR flows | Not created |
| `devguard-react-ux` | React onboarding, dashboards, real progress and accessibility | Not created |
| `devguard-universal-discovery` | Project type discovery, monorepos and capability registry | Not created |
| `devguard-runner-isolation` | Sandbox, SSRF, execution runner isolation and cleanup | Not created |
| `devguard-ai-test-planning` | Python agent test planning, diagnostics and evidence | Not created |
| `devguard-ai-fix-approvals` | Scoped approvals, fix retry, verification and human handoff | Not created |
| `devguard-quota-billing` | Trial entitlements, subscription and quota integrity | Not created |
| `devguard-artifact-privacy` | Private logs, reports, screenshots, patches and retention | Not created |
| `devguard-platform-adapters` | Adapter contracts for web, API, full-stack, AI, mobile and desktop | Not created |
| `devguard-observability-release` | Operational monitoring, incident and launch security gates | Not created |

## Development sequence
1. Complete and review full acceptance catalog for all launch features; mark non-launched platforms as planned.
2. Review RBAC permission matrix and financial values that remain proposed.
3. Approve subsystem specs, then produce tasks with `superpowers:writing-plans`.
4. Explicit permission is required before DevGuard application code, dependency installation, repository creation or application-test execution. This does not restrict separately authorized isolated orchestrator tooling work or its synthetic tests.
5. At implementation: RED (watch correct failure) → GREEN (minimal code) → REFACTOR → verify suite → review → commit.

## Approved local orchestrator Git workflow
- This workflow applies only to future, explicitly approved local orchestrator tasks; it does not authorize DevGuard application implementation or R1 task execution.
- Create one isolated `task/Txx` branch from the current verified `dev` state for each approved task. Keep failed task branches and investigation logs for review.
- Integrate a task into `dev` and push to `origin/dev` only after its independent quality gates pass, its diff contains only authorized changes, and `dev` has not changed concurrently. Do not continue to the next task until integration and push succeed.
- Respect branch protection. If GitHub requires a pull request or other approval to update `dev`, stop and wait for that approval; never bypass protection.
- Never automatically push or merge to `main`. Never force-push, rebase around conflicts, or discard failed task state.

## Truthfulness and gates
- Never claim that a proposed custom skill is installed or verified.
- Never claim a test passed without real execution evidence.
- No customer code runs on trusted Laravel/Python hosts.
- Security-Gated Launch (PD-039) is mandatory.

## Quality, Speed & Token-Efficiency Policy

1. Work on one bounded task or work package at a time.
2. Read only relevant project files and dependencies; avoid repeated repository-wide scans.
3. Use the approved PRD, acceptance specifications and implementation plan as the source of truth.
4. Prefer the smallest correct change that satisfies the acceptance criteria.
5. Reuse existing patterns and applicable skills instead of rebuilding solutions unnecessarily.
6. Use deterministic local validation before expensive AI-based analysis, within the authorized scope.
7. Apply risk-based verification, with strongest scrutiny for authorization, tenant isolation, billing, sandbox execution, secrets and GitHub writes.
8. When test execution is authorized, run relevant tests for small changes; run broader regressions at defined integration gates rather than the full suite after every edit.
9. Never sacrifice security or test coverage to save tokens.
10. Stop when acceptance criteria are satisfied; report modified files, validation evidence, failures and remaining risks.
11. Do not independently expand scope, select paid providers or perform deployments.
12. Keep DevGuard application work documentation-only until separate explicit product-owner authorization. Orchestrator-tooling authorization does not approve R1 task execution, real task approvals, application-test execution, dependency installation for the application, or remote writes.
