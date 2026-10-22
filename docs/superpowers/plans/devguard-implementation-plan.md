# DevGuard AI Implementation Plan

> **For agentic workers:** After explicit implementation/test authorization and approved subsystem specifications, use `superpowers:executing-plans` for native task-by-task execution or `superpowers:subagent-driven-development` only if the user selects delegation. These checkboxes describe future work; this documentation task executes none of it.

**Goal:** Deliver the PRD v4.3 platform in secure vertical slices, with each task traced to reviewed acceptance specifications and each later platform enabled only with its own evidence.
**Architecture:** Trusted self-managed Laravel API/workers and Python orchestration control current membership, approval, quota, capability and budget policy. React static frontend/CDN consumes truthful state; managed data/queues as appropriate, private objects and secrets support the control plane. All untrusted checkout/build/tests/browser/patch execution runs in external managed isolated sandboxes through provider-agnostic adapters.
**Tech stack:** PRD-mandated Laravel Socialite GitHub OAuth, Python agent/orchestration, React frontend; initial React/Vite/vanilla Web testing and feasible Playwright. No provider, exact version, final module layout or dependency installation is selected by this document.
**Spec:** [Master acceptance test plan](../tests/devguard-master-test-plan.md), based on [PRD v4.3](../../product/PRD-v4.3.md), PD-001–PD-042, SG-01–SG-12 and detailed §§3–9.
**Status:** PRD-reconciled planning draft. Source review is complete; product/security/finance approval, ND01–ND15 decisions and subsystem designs remain open. 30 work packages map 240 unique scenario IDs, including all original 106. “Verified ID mapping” means document cross-reference validation, never executed tests.
**Scope now:** Two Markdown documents only. No application code, dependencies, scaffolding, tests, Git initialization, deployment, purchase or external writes are authorized.

## 1. Global constraints

- Explicit informed approval is required before **any source modification** (including isolated patch creation) and fix PR write, bound to repository/SHA/change scope/budget. Nonblocking evidence-grounded inline comments under PD-021 are a narrow exception to fix-write approval for non-mutating review comments only; they confer no source-edit authority.
- Draft PR only; never auto-merge. Failed verification permits only separately explicitly approved, screened, plainly unverified human-review-only Draft PR publication.
- Current server/worker policy controls tenants/roles/scope. Never trust submitted workspace IDs, stale UI privileges or AI permission claims.
- Untrusted code runs solely in external managed isolated runners; no customer code or unsafe fallback on trusted Laravel/Python hosts. Vetted security/isolation precedes provider price/performance.
- Production tests are authorized and non-destructive. State-changing tests require validated isolated authorized staging/test targets. Runtime network/phase policy defeats private/internal/metadata/DNS/redirect tricks.
- Test-account secrets are brokered to trusted login only, withheld from AI/untrusted commands and evidence. CAPTCHA/MFA bypass is forbidden.
- One bounded full Scan Run consumes one Scan credit regardless of internal test count. Scan/Fix ledgers are independent, shared at workspace level, atomic and idempotent. Executed unsuccessful Fix jobs count under the approved error policy; internal retries/verification add no credits.
- Basic **10** and Pro **30** monthly scans are approved. One qualifying real-user trial scan before payment is approved. No BYOK/pay-per-scan/automatic overage.
- Prices $19/$39/$99, Team 100 scans, Fix 5/15/40, 30 retention days, 3 retries, margin 60/40/20% and RPO/RTO 1h/4h remain proposals. ND decisions must close before exact-value assertions depend on them.
- Unlimited paid repository connections do not clone/execute/consume a credit. Execution/API/storage fair-use limits are separate approved policy.
- Dashboard+Email only; no Telegram or routine success-email spam. Private artifacts, expiring links, sanitization, uniform paid report retention and separate audit/financial lifecycle are required.
- Real Queued/Provisioning/Running/Analyzing/Finalizing/Cleaning/Completed events and appropriate Failed/Cancelled/Partial/Blocked states drive UI/Checks; no invented percentages or passed unsupported work.
- R1 Web → R2 API then Full-Stack → R3 AI Applications → R4 Android then iOS → R5 Desktop. Detecting/planning a profile never claims support; Scan and Fix capabilities are independent.
- Public launch requires all 12 security areas with actual evidence, no unresolved exploitable critical/high findings in agreed scope, sustainable measured operations, provider/privacy/legal validation, authorized limited pilot and explicit Go/No-Go. Every later available platform repeats relevant gates.
- All 240 scenarios remain **SPECIFIED / NOT EXECUTED**. No evidence or execution result is created by this plan.

## 2. Skills, approvals and execution discipline

### 2.1 Applicable skills

`devguard-acceptance-specs` is discoverable but still a draft; its pressure-testing/human review is not assumed complete. Use it for every task's clauses, case shape, priorities, negative/concurrency coverage and evidence honesty. Use `superpowers:using-superpowers` for task routing, `superpowers:brainstorming` to resolve ND decisions and design each subsystem, and `superpowers:writing-plans` to refine approved designs into executable child tasks.

Once execution is separately authorized, use `superpowers:test-driven-development`, `superpowers:systematic-debugging` when results are unexpected, `superpowers:verification-before-completion`, `superpowers:requesting-code-review` and `superpowers:receiving-code-review` as applicable. Future isolation/branch work uses `superpowers:using-git-worktrees`; integration uses `superpowers:finishing-a-development-branch` under the authorized repository workflow, without automatic main merge or force push. No such engineering action occurs now.

The specialized names in task details are **planned, not authored/available**: `devguard-laravel-control-plane`, `devguard-github-integration`, `devguard-react-ux`, `devguard-universal-discovery`, `devguard-runner-isolation`, `devguard-ai-test-planning`, `devguard-ai-fix-approvals`, `devguard-quota-billing`, `devguard-artifact-privacy`, `devguard-platform-adapters`, `devguard-observability-release`. They identify domain expertise, not installed tooling. Use available methodology and approved specifications when a custom skill adds no reusable value. If skill authoring is separately scoped, use `superpowers:writing-skills`; never claim a planned skill is validated.

### 2.2 Design and authorization gates

The user requested both documents; this permits completing these reviewable artifacts without another documentation-approval loop. It does not approve unknown operational policy, subsystem design or engineering execution. T00/T01 carry the remaining review/design work explicitly. No exact role grant, refund rule, vendor, support version or command may be guessed to make a blocked child task “ready.”

The PRD now exists and contains all PD/SG definitions; the old “missing PRD” blocker is removed. ND01–ND15 in the test plan are the remaining policy/design limitations. The repository is a documentation starter, so module paths below are **proposed responsibilities**, not existing application files. Before execution, approved subsystem designs must lock actual paths, interfaces, migrations, routes, queue semantics, tooling/version constraints and exact verification commands. No application harness or passing result is implied by a target path.

### 2.3 Future per-case TDD and task acceptance

Each implementation work package must be decomposed into reviewer-sized child tasks. For each mapped case/parameter:

1. Read the approved PD/SG clauses, expected result, current scope/policy and ND dependencies. Build only authorized fixtures; security attacks and recovery drills require isolated environments.
2. Write the named assertion at the mapped target. Observe its intended behavioral failure with the exact approved command. Missing tools/credentials/infrastructure are blocked prerequisites, not meaningful RED evidence.
3. Implement minimum approved behavior, rerun and inspect the actual outcome. Refactor after GREEN without changing the accepted contract.
4. Run relevant neighboring regressions/lint/build checks only during authorized execution; use real persistence and actual integration for financial, authorization, runner/provider and recovery claims.
5. Review security/finance/privacy and final diff; record case/parameter/PD/SG/source/policy/runner identities plus private sanitized evidence and actual limitations.
6. Follow the separately authorized safe feature-branch workflow: only intended non-secret files, descriptive commit, verified push after required checks; no auto-main merge, force push or unrelated changes. This request explicitly prohibits Git initialization or push.

Specification status stays separate from actual execution outcome. A written runtime expected BLOCKED/UNSUPPORTED result never means the case ran. Unavailable future platforms do not certify native coverage.

## 3. Review focus and cross-task evidence closure

| Risk combination | Observable acceptance | Cases / owners |
|---|---|---|
| Queued/stale-browser actor loses permission | Current dispatch/action denies stale authority | DG-IAM-006, DG-UX-003 / T02, T08, T19 |
| Reservation or remote success precedes a lost acknowledgment | One reconciled ledger/job/provider effect | DG-CREDIT-006, DG-GH-004, DG-FIX-008, DG-BILL-004 / T04, T09, T15, T16, T18 |
| Redirect/DNS/phase or hostile prompt attempts to change policy | Trusted runtime enforcement keeps scope/secrets/budget intact | DG-RUN-004/013/017/018, DG-SECRET-005, DG-AI-009 / T06, T23, T26 |
| Supported layer beside missing/unsupported/unverified layer | Partial coverage stays explicit; no fabricated success | DG-DISC-002/010, DG-FULL-002, DG-REPORT-002/005 / T07, T12, T17 |
| Retained link/notification/device state survives authorization change | Current access and lifecycle prevent disclosure/reuse | DG-REPORT-006/012, DG-NOTIFY-007, DG-PLAT-006 / T03, T20, T21/native tasks |

Early boundaries can be implemented with controlled consumers, but their later integration evidence remains incomplete until actual producers/consumers exist. T03 storage policy closes capture integration in T06/T10/T23; T04 ledger closes dispatch/retry/crash integration in T08/T15/T18; T05 repository policy closes checkout and provider writes in T08/T09/T15. T17 integrates currently available producers, then repeats for R2–R5. T21 contract fixtures are not native runner evidence: T27/T28/T29 must instantiate the actual OS/device cases. T22 consumes final release-scope integration results, not only local unit checks.

## 4. Dependency and release ordering

Task IDs T00–T22 preserve the earlier plan's identities. T23–T29 add explicit missing work. IDs are identifiers, not numerical scheduling order; **the rows below are in topological execution order**. T22 is first applied to R1 and repeated as a release-review activity for each later matrix entry, without a graph dependency on future-only implementation. No deployment is implied by a reviewed gate.

| Task | Work package | Prerequisite tasks | Release | Canonical owned acceptance IDs |
|---|---|---|---|---|
| T00 | Approve the reconciled requirements baseline | Canonical PRD available | Documentation; all releases | All 240 cases: source/decision/design review |
| T01 | Approve subsystem contracts, fixtures and child plans | T00 | Before engineering | All 240 cases: source/decision/design review |
| T02 | GitHub OAuth identity, current RBAC and tenancy | T01 | R1 core; repeated all releases | DG-IAM-001, DG-IAM-002, DG-IAM-003, DG-IAM-004, DG-IAM-005, DG-IAM-006, DG-IAM-007, DG-IAM-008, DG-IAM-009, DG-IAM-010, DG-IAM-011, DG-IAM-012, DG-IAM-013, DG-IAM-014, DG-IAM-015, DG-IAM-016 |
| T03 | Private evidence, secrets and retention access | T02 | R1 core; all release captures | DG-REPORT-003, DG-REPORT-004, DG-REPORT-006, DG-REPORT-010, DG-REPORT-011, DG-REPORT-012, DG-REPORT-013 |
| T04 | Atomic bounded Scan/Fix accounting | T02 | R1 ledger; R2 composite regression | DG-CREDIT-001, DG-CREDIT-002, DG-CREDIT-003, DG-CREDIT-004, DG-CREDIT-005, DG-CREDIT-006, DG-CREDIT-007, DG-CREDIT-008, DG-CREDIT-009, DG-CREDIT-010, DG-CREDIT-011, DG-CREDIT-012, DG-CREDIT-013, DG-CREDIT-014, DG-CREDIT-015 |
| T25 | Trusted hybrid hosting and provider assurance | T02, T03, T04 | R1 foundation; reevaluate later providers | DG-HOST-001, DG-HOST-002, DG-HOST-003 |
| T05 | OAuth/App separation and repository connections | T02, T03, T25 | R1 GitHub binding | DG-GH-001, DG-GH-002, DG-GH-005, DG-GH-010, DG-GH-015 |
| T06 | Managed sandbox, network and lifecycle containment | T02, T03, T25 | R1 runner; repeated per profile | DG-RUN-001, DG-RUN-002, DG-RUN-003, DG-RUN-004, DG-RUN-005, DG-RUN-006, DG-RUN-007, DG-RUN-008, DG-RUN-009, DG-RUN-010, DG-RUN-011, DG-RUN-012, DG-RUN-013, DG-RUN-014, DG-RUN-015, DG-RUN-016, DG-RUN-017, DG-RUN-018 |
| T07 | Read-only discovery and independent capabilities | T05, T06 | R1 discovery; phased detected candidates | DG-DISC-001, DG-DISC-002, DG-DISC-003, DG-DISC-004, DG-DISC-005, DG-DISC-006, DG-DISC-007, DG-DISC-008, DG-DISC-009, DG-DISC-010, DG-DISC-011 |
| T08 | Authorized scan admission and truthful job lifecycle | T04, T05, T06, T07 | R1 first scan slice | DG-JOB-001, DG-JOB-002, DG-JOB-003, DG-JOB-004, DG-JOB-005, DG-JOB-006, DG-JOB-007, DG-JOB-008, DG-JOB-009, DG-JOB-010, DG-JOB-011 |
| T09 | Verified triggers, Checks, summaries and diff comments | T04, T05, T08 | R1 automated review surfaces | DG-GH-003, DG-GH-004, DG-GH-006, DG-GH-007, DG-GH-008, DG-GH-009, DG-GH-011, DG-GH-012, DG-GH-013, DG-GH-014, DG-GH-016, DG-GH-017 |
| T10 | R1 baseline checks and safe browser execution | T03, T06, T08 | R1 Web only | DG-WEB-001, DG-WEB-002, DG-WEB-003, DG-WEB-004, DG-WEB-005, DG-WEB-006, DG-WEB-007 |
| T23 | Brokered test accounts and guided trusted login | T02, T03, T06, T10 | R1 optional authenticated E2E; reused later | DG-SECRET-001, DG-SECRET-002, DG-SECRET-003, DG-SECRET-004, DG-SECRET-005, DG-SECRET-006, DG-SECRET-007 |
| T13 | R1 risk planning without AI authority | T03, T06, T07, T08, T10 | R1 planner; not the R3 evaluation product | DG-AI-001, DG-AI-004, DG-AI-005, DG-WEB-008 |
| T14 | Grouped Fix Plans and current scoped approvals | T02, T04, T08, T13 | R1 fix admission | DG-FIX-001, DG-FIX-002, DG-FIX-003, DG-FIX-004, DG-FIX-010, DG-FIX-011, DG-FIX-012, DG-FIX-013 |
| T15 | Isolated verification, bounded retries and Draft PR handoff | T03, T05, T06, T09, T10, T14 | R1 Web fixes; later profiles conditional | DG-FIX-005, DG-FIX-006, DG-FIX-007, DG-FIX-008, DG-FIX-009, DG-FIX-014, DG-FIX-015, DG-FIX-016, DG-FIX-017, DG-FIX-018, DG-FIX-019 |
| T16 | Qualifying trial and monthly commercial lifecycle | T02, T04, T25 | R1 commercial completeness before public release | DG-BILL-001, DG-BILL-002, DG-BILL-003, DG-BILL-004, DG-BILL-005, DG-BILL-006, DG-BILL-007, DG-BILL-008, DG-BILL-009, DG-BILL-010, DG-BILL-011, DG-BILL-012, DG-BILL-013, DG-BILL-014, DG-BILL-015, DG-BILL-016, DG-BILL-017 |
| T17 | Advanced reports and truthful component coverage | T03, T08 | R1 baseline; integrate every available later producer | DG-REPORT-001, DG-REPORT-002, DG-REPORT-005, DG-REPORT-007, DG-REPORT-008, DG-REPORT-009 |
| T18 | Backup, recovery, containment and incident operations | T08, T09, T15, T16, T17 | R1 operational gates; rerun later profiles | DG-REC-001, DG-REC-002, DG-REC-003, DG-REC-004, DG-REC-005, DG-REC-006, DG-REC-007, DG-REC-008, DG-REC-009, DG-REC-010, DG-REC-011, DG-REC-012 |
| T19 | Zero-configuration UI, real progress and informed approval | T05, T08, T14, T17, T23 | R1 Dashboard; later platform views | DG-UX-001, DG-UX-002, DG-UX-003, DG-UX-004, DG-UX-005, DG-UX-006, DG-UX-007, DG-UX-008 |
| T20 | Dashboard/Email priority notifications | T02, T03, T17, T18 | R1 approved channels only | DG-NOTIFY-001, DG-NOTIFY-002, DG-NOTIFY-003, DG-NOTIFY-004, DG-NOTIFY-005, DG-NOTIFY-006, DG-NOTIFY-007 |
| T21 | Shared adapter/capability contract and unsupported denial | T06, T07, T08 | R1 contract; R2–R5 instantiate native lifecycle | DG-PLAT-001, DG-PLAT-005, DG-PLAT-006, DG-PLAT-007 |
| T24 | Measured cost, capacity and sustainable pricing | T08, T15, T16, T18 | R1 viability gate; repeat per later platform | DG-COST-001, DG-COST-002, DG-COST-003, DG-COST-004, DG-COST-005, DG-COST-006 |
| T22 | Evidence-backed security and release review | T02, T03, T04, T05, T06, T07, T08, T09, T10, T13, T14, T15, T16, T17, T18, T19, T20, T21, T23, T24, T25 | R1 review; repeat for each release's available scope | DG-GATE-001, DG-GATE-002, DG-GATE-003, DG-GATE-004, DG-PLAT-015, DG-GATE-005, DG-GATE-006, DG-GATE-007, DG-GATE-008, DG-GATE-009, DG-GATE-010, DG-GATE-011, DG-GATE-012 |
| T11 | R2 vetted Backend/API execution | T06, T08, T22, T23 | R2; after R1 acceptance | DG-API-001, DG-API-002, DG-API-003, DG-API-004, DG-API-005, DG-API-006, DG-API-007 |
| T12 | R2 bounded Web+API composite | T04, T10, T11 | R2 Full-Stack after API | DG-FULL-001, DG-FULL-002, DG-FULL-003, DG-FULL-004, DG-FULL-005 |
| T26 | R3 agent/chatbot/RAG evaluation adapter | T06, T08, T13, T21, T24, T12 | R3 after R2 acceptance review | DG-AI-002, DG-AI-003, DG-AI-006, DG-AI-007, DG-AI-008, DG-AI-009, DG-AI-010, DG-AI-011, DG-AI-012, DG-AI-013 |
| T27 | R4 Android native profiles | T06, T21, T26 | R4 Android first; after R3 acceptance review | DG-PLAT-002, DG-PLAT-008, DG-PLAT-009 |
| T28 | R4 compliant iOS native profiles | T06, T21, T27 | R4 iOS after Android acceptance review | DG-PLAT-003, DG-PLAT-010, DG-PLAT-011 |
| T29 | R5 Desktop OS-specific profiles | T06, T21, T28 | R5 after mobile acceptance review | DG-PLAT-004, DG-PLAT-012, DG-PLAT-013, DG-PLAT-014 |

### 4.1 Vertical slices and later releases

| Stage | Task sequence / evidence | Exit condition |
|---|---|---|
| Reviewed foundation | T00, T01, T02, T03, T04, T25 | Source/decision/design, tenancy, credits, private data and approved trusted/untrusted boundaries |
| First secure scan slice | T05, T06, T07, T08, baseline T10/T17 | Real GitHub→bounded isolated deterministic scan→private report with authorization/quota/stages from the first path |
| Risk-based and authenticated Web | T09, T10, T23, T13; integrate T17 | Feasible risk-targeted browser, brokered login, safe partial/live tests, accurate Checks/comments |
| Approved fix slice | T14, T15; integrate T17 | Root-cause plan→explicit scope→isolated verification→Draft PR or truthful screened handoff |
| Commercial/release hardening | T16, T18, T19, T20, T21, T24; T22(R1) | Shared team entitlement, smart channels, private lifecycle, measured cost, recovery/incident/pilot/all SG evidence |
| R2 | T11 then T12; repeat T17/T18/T24/T22(R2) | Vetted API/role/fixture and bounded one-Scan composite evidence; no R1 dependency on these future adapters |
| R3 | T26; repeat shared report/recovery/cost/T22(R3) | Real deterministic/rubric/tool/grounding/injection/cost evidence; no uncontrolled real tool side effects |
| R4 | T27 then T28; repeat shared native gates and T22 | Independent Android then compliant iOS runner and privacy/cleanup evidence |
| R5 | T29 per vetted OS profile; repeat T22 | Electron/Tauri then approved native profiles; per-OS containment/cleanup/operational viability |

R1 core tasks T13/T15/T17 use only R1 capabilities. T13 does **not** require T26; R1 fixes do **not** require R2 API verification; R1 reporting does **not** require actual Full-Stack/AI/native results. Each later release must complete the relevant earlier release review and its own compatibility/provider decisions before claiming availability; roadmap inclusion alone is never that evidence.

## 5. Detailed work packages

Every future implementation package uses the common TDD/review cycle in §2.3, plus the task-specific fixtures/assertions below. Paths and interface descriptions must be finalized by T01 rather than silently promoted to approved architecture. No checked engineering steps or test commands/results are claimed.

### T00 — Approve the reconciled requirements baseline

**Prerequisites:** Read canonical PRD; available now. **Release:** Documentation; all releases.
**Skills:** `devguard-acceptance-specs` and devguard-acceptance-specs; superpowers:brainstorming.
**PD references:** PD-001, PD-002, PD-003, PD-004, PD-005, PD-006, PD-007, PD-008, PD-009, PD-010, PD-011, PD-012, PD-013, PD-014, PD-015, PD-016, PD-017, PD-018, PD-019, PD-020, PD-021, PD-022, PD-023, PD-024, PD-025, PD-026, PD-027, PD-028, PD-029, PD-030, PD-031, PD-032, PD-033, PD-034, PD-035, PD-036, PD-037, PD-038, PD-039, PD-040, PD-041, PD-042.
**Security Gates:** SG-01, SG-02, SG-03, SG-04, SG-05, SG-06, SG-07, SG-08, SG-09, SG-10, SG-11, SG-12.
**Acceptance IDs:** All catalog IDs; see full PD/SG and owner matrices.
**Decision dependencies:** NEEDS_DECISION ND01, ND02, ND03, ND04, ND05, ND06, ND07, ND08, ND09, ND10, ND11, ND12, ND13, ND14, ND15.
**Proposed files/responsibilities:** docs/superpowers/tests/devguard-master-test-plan.md; docs/superpowers/plans/devguard-implementation-plan.md.
**Interface / producer–consumer contract:** PRD PD/SG clauses → reviewed case/decision/task inventory. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Read the canonical source and confirm all 42 PD/12 SG definitions; preserve its identity and approved-versus-proposed distinctions.
- [ ] Review each original ID's disposition and each new scenario against its observable clause; retain no invented role grant or vendor commitment.
- [ ] Assign owners and resolve ND01–ND15 where necessary for the next slice; keep the remaining markers explicit.
- [ ] Review and approve the written catalog/traceability with product, security and finance before claiming executable policy completeness.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T01 — Approve subsystem contracts, fixtures and child plans

**Prerequisites:** T00. **Release:** Before engineering.
**Skills:** `devguard-acceptance-specs` and superpowers:brainstorming; superpowers:writing-plans; devguard-acceptance-specs.
**PD references:** PD-001, PD-002, PD-003, PD-004, PD-005, PD-006, PD-007, PD-008, PD-009, PD-010, PD-011, PD-012, PD-013, PD-014, PD-015, PD-016, PD-017, PD-018, PD-019, PD-020, PD-021, PD-022, PD-023, PD-024, PD-025, PD-026, PD-027, PD-028, PD-029, PD-030, PD-031, PD-032, PD-033, PD-034, PD-035, PD-036, PD-037, PD-038, PD-039, PD-040, PD-041, PD-042.
**Security Gates:** SG-01, SG-02, SG-03, SG-04, SG-05, SG-06, SG-07, SG-08, SG-09, SG-10, SG-11, SG-12.
**Acceptance IDs:** All catalog IDs; see full PD/SG and owner matrices.
**Decision dependencies:** NEEDS_DECISION ND01, ND02, ND03, ND04, ND05, ND06, ND07, ND08, ND09, ND10, ND11, ND12, ND13, ND14, ND15.
**Proposed files/responsibilities:** docs/superpowers/specs/<subsystem>-design.md (future only).
**Interface / producer–consumer contract:** Approved baseline → ExecutionScope, policy/ledger/job/adapter/evidence contracts and exact harness commands. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Design small identity, credit, runner, GitHub, scan/report, approval/fix, billing and platform boundaries from the PRD; pin schemas/state transitions and the exact approval invalidation rules.
- [ ] Specify dedicated authorized fixtures, named assertions and exact future command/output oracles for each mapped case, including integration versus mock boundaries.
- [ ] Review file/module layouts, route/worker contracts, versions, provider and network/secret design; record unselected dependencies as decisions rather than scaffolding now.
- [ ] Obtain written subsystem design approval and refine the work packages below into independently reviewed TDD child tasks; separate authorization remains necessary for all execution.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T02 — GitHub OAuth identity, current RBAC and tenancy

**Prerequisites:** T01. **Release:** R1 core; repeated all releases.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-laravel-control-plane.
**PD references:** PD-008, PD-009, PD-010, PD-013, PD-039.
**Security Gates:** SG-01, SG-02, SG-08.
**Acceptance IDs:** DG-IAM-001, DG-IAM-002, DG-IAM-003, DG-IAM-004, DG-IAM-005, DG-IAM-006, DG-IAM-007, DG-IAM-008, DG-IAM-009, DG-IAM-010, DG-IAM-011, DG-IAM-012, DG-IAM-013, DG-IAM-014, DG-IAM-015, DG-IAM-016.
**Decision dependencies:** NEEDS_DECISION ND01.
**Proposed files/responsibilities:** backend/app/Domain/Identity/{GitHubIdentity,AuthorizationService,ExecutionScope}.php.
**Interface / producer–consumer contract:** OAuth identity/current membership → AuthorizationDecision(actor,action,scope,policy). T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify Socialite-only valid/cancel/state/code/link/email-change/logout/session fixtures and assert no App token can authenticate a user.
- [ ] Define F-RBAC from reviewed ND01 grants for each Owner/Admin/Member/Viewer action, including membership/worker/billing surfaces.
- [ ] Observe denial/revocation/privileged-field/tenant-substitution failures, then implement trusted identity and current action-bound policy.
- [ ] Verify API and worker access boundaries with actual persistence; recheck revoked authorization at T08 dispatch and T19 stale-browser integration.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T03 — Private evidence, secrets and retention access

**Prerequisites:** T02. **Release:** R1 core; all release captures.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-artifact-privacy.
**PD references:** PD-010, PD-012, PD-018, PD-027, PD-035.
**Security Gates:** SG-06, SG-10.
**Acceptance IDs:** DG-REPORT-003, DG-REPORT-004, DG-REPORT-006, DG-REPORT-010, DG-REPORT-011, DG-REPORT-012, DG-REPORT-013.
**Decision dependencies:** NEEDS_DECISION ND05.
**Proposed files/responsibilities:** backend/app/Domain/Artifacts/{ArtifactAccess,ArtifactLifecycle}.php; runner/devguard/evidence/policy.py.
**Interface / producer–consumer contract:** Current authority + job/SHA → private ArtifactRef and expiry/lifecycle decision. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Assert denied direct/signed/application access for cross-tenant, expired and revoked artifact references using a controlled clock.
- [ ] Specify no-secret evidence oracle and encryption/access handling; verify capture/log/error/patch paths with synthetic sentinels.
- [ ] Implement private lifecycle metadata and uniform paid report duration without promoting proposed 30 days; retain separate financial/audit policy.
- [ ] Verify capture-to-storage integration when runners exist and later report rendering; do not count a storage-only check as completed browser secret evidence.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T04 — Atomic bounded Scan/Fix accounting

**Prerequisites:** T02. **Release:** R1 ledger; R2 composite regression.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-quota-billing.
**PD references:** PD-010, PD-015, PD-016, PD-017, PD-036, PD-041.
**Security Gates:** SG-09.
**Acceptance IDs:** DG-CREDIT-001, DG-CREDIT-002, DG-CREDIT-003, DG-CREDIT-004, DG-CREDIT-005, DG-CREDIT-006, DG-CREDIT-007, DG-CREDIT-008, DG-CREDIT-009, DG-CREDIT-010, DG-CREDIT-011, DG-CREDIT-012, DG-CREDIT-013, DG-CREDIT-014, DG-CREDIT-015.
**Decision dependencies:** NEEDS_DECISION ND03, ND04.
**Proposed files/responsibilities:** backend/app/Domain/Credits/{Ledger,Reservation,Reconciliation}.php.
**Interface / producer–consumer contract:** Authorized logical operation → durable separate Scan/Fix reservation/settlement. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify approved Basic=10/Pro=30 Scan initialization and one-credit-per-full-run assertions, with no implied Team or Fix numerical cap.
- [ ] Control last-credit races, duplicate transition replay and two-tenant attempts against actual transactional persistence.
- [ ] Implement reservation/idempotency/settlement under ND03-approved policies, including unsuccessful executed Fix jobs and included internal retries.
- [ ] Fault-inject job/ledger crash boundaries in T08/T15/T18 and test monthly/reset/cancel/platform-error rules; composite one-Scan regression closes in T12.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T25 — Trusted hybrid hosting and provider assurance

**Prerequisites:** T02, T03, T04. **Release:** R1 foundation; reevaluate later providers.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-observability-release; devguard-runner-isolation.
**PD references:** PD-025, PD-037, PD-039.
**Security Gates:** SG-04, SG-06.
**Acceptance IDs:** DG-HOST-001, DG-HOST-002, DG-HOST-003.
**Decision dependencies:** NEEDS_DECISION ND06, ND15.
**Proposed files/responsibilities:** docs/superpowers/specs/hosting-design.md; future private infrastructure definitions chosen by design.
**Interface / producer–consumer contract:** Trusted Laravel/Python/private services/static React boundaries → vetted external-runner/provider policy. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify trusted self-managed API/workers/orchestration, managed data/queues as appropriate, private objects/secrets and React static/CDN boundaries without choosing a vendor implicitly.
- [ ] Evaluate sandbox/cloud/model/payment/email/vault/queue/macOS candidates for security, eligibility, geography, subprocessors and operation before cost comparison.
- [ ] Write denied trusted-host execution/service-access assertions, then implement only separately authorized approved infrastructure boundaries.
- [ ] Verify provider assurance and real runner isolation through T06/T21/native integration; public billing/launch remains blocked on ND06/ND15 validation.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T05 — OAuth/App separation and repository connections

**Prerequisites:** T02, T03, T25. **Release:** R1 GitHub binding.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-github-integration.
**PD references:** PD-009, PD-010, PD-020, PD-033.
**Security Gates:** SG-03.
**Acceptance IDs:** DG-GH-001, DG-GH-002, DG-GH-005, DG-GH-010, DG-GH-015.
**Decision dependencies:** NEEDS_DECISION ND06.
**Proposed files/responsibilities:** backend/app/Domain/GitHub/{Connection,RepositoryBinding,InstallationPolicy}.php.
**Interface / producer–consumer contract:** Authenticated user + installation scope → current immutable RepositoryBinding. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify callback integrity, least-privilege installation, selected repositories and revoked checkout fixtures distinct from user login.
- [ ] Assert unlimited paid connections without clone/execution/credit effects; separate fair-use execution limits from connection count.
- [ ] Implement current repository/SHA authority, lifecycle handling and safe credential delivery according to vetted App policy.
- [ ] Verify provider integration, denied repository access and revocation; deferred checkout/write paths revalidate in T08/T09/T15.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T06 — Managed sandbox, network and lifecycle containment

**Prerequisites:** T02, T03, T25. **Release:** R1 runner; repeated per profile.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-runner-isolation.
**PD references:** PD-006, PD-008, PD-013, PD-016, PD-022, PD-023, PD-024, PD-025, PD-026, PD-027, PD-037, PD-039, PD-041.
**Security Gates:** SG-04, SG-05, SG-06, SG-07, SG-08, SG-09.
**Acceptance IDs:** DG-RUN-001, DG-RUN-002, DG-RUN-003, DG-RUN-004, DG-RUN-005, DG-RUN-006, DG-RUN-007, DG-RUN-008, DG-RUN-009, DG-RUN-010, DG-RUN-011, DG-RUN-012, DG-RUN-013, DG-RUN-014, DG-RUN-015, DG-RUN-016, DG-RUN-017, DG-RUN-018.
**Decision dependencies:** NEEDS_DECISION ND04, ND06.
**Proposed files/responsibilities:** backend/app/Domain/Runners/Admission.php; runner/devguard/isolation/{lifecycle,network,filesystem,budgets}.py.
**Interface / producer–consumer contract:** ExecutionScope + vetted provider/capability → isolated bounded run or explicit denial. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify provider-agnostic lifecycle and no-fallback admission; authorize an isolated adversarial fixture environment before any future attack execution.
- [ ] Observe host/tenant/secret/filesystem/resource escape, DNS/redirect/internal-address and phase egress failures with explicit policy bounds.
- [ ] Implement below-prompt network/filesystem/budget isolation; positive allowlist access is as deliberate as forbidden egress denial.
- [ ] Verify success/failure/cancel/crash/contamination cleanup and ephemeral checkout with real infrastructure; no trusted-host execution or full-repo product persistence.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T07 — Read-only discovery and independent capabilities

**Prerequisites:** T05, T06. **Release:** R1 discovery; phased detected candidates.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-universal-discovery.
**PD references:** PD-004, PD-007, PD-022, PD-023, PD-029, PD-041, PD-042.
**Security Gates:** SG-04.
**Acceptance IDs:** DG-DISC-001, DG-DISC-002, DG-DISC-003, DG-DISC-004, DG-DISC-005, DG-DISC-006, DG-DISC-007, DG-DISC-008, DG-DISC-009, DG-DISC-010, DG-DISC-011.
**Decision dependencies:** Approved behavior; actual subsystem/provider/profile design still requires review.
**Proposed files/responsibilities:** agent/devguard/discovery/{inspector,topology,capabilities}.py.
**Interface / producer–consumer contract:** Pinned repository metadata → confidence/evidence and versioned Scan/Fix capability profiles. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify React/Vite/vanilla and ambiguous/unsupported/mixed monorepo fixtures, including later API/native/AI detections.
- [ ] Assert safe defaults permit useful partial scans without staging/login and ask only necessary override questions.
- [ ] Implement read-only topology/confidence and trusted capability selection; metadata/script payloads cannot execute on trusted hosts.
- [ ] Verify PLANNED/SUPPORTED/PARTIAL/BLOCKED/UNSUPPORTED and per-version metadata; Scan support never implies verified Fix support.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T08 — Authorized scan admission and truthful job lifecycle

**Prerequisites:** T04, T05, T06, T07. **Release:** R1 first scan slice.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-laravel-control-plane.
**PD references:** PD-001, PD-003, PD-006, PD-010, PD-016, PD-019.
**Security Gates:** SG-02, SG-09.
**Acceptance IDs:** DG-JOB-001, DG-JOB-002, DG-JOB-003, DG-JOB-004, DG-JOB-005, DG-JOB-006, DG-JOB-007, DG-JOB-008, DG-JOB-009, DG-JOB-010, DG-JOB-011.
**Decision dependencies:** NEEDS_DECISION ND03, ND10.
**Proposed files/responsibilities:** backend/app/Domain/Jobs/{Admission,StateMachine,ProgressStream}.php.
**Interface / producer–consumer contract:** Authorized repository/live target + ledger/capability → immutable JobRef and ordered JobEvents. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify valid/invalid/duplicate manual admission and separate live-site authorization, keeping deployed-SHA uncertainty ND10 explicit.
- [ ] Observe concurrent submissions, cancel/completion races, stale progress and heartbeat/stream/reconnect failures with controlled clocks.
- [ ] Implement durable admission, scoped dispatch and actual Queued/Provisioning/Running/Analyzing/Finalizing/Cleaning/Completed plus appropriate exceptional states.
- [ ] Verify monotonic private progress, revoked dispatch authority, unavailable-runner denial and ND03 settlement; do not invent percentages or successful skipped scans.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T09 — Verified triggers, Checks, summaries and diff comments

**Prerequisites:** T04, T05, T08. **Release:** R1 automated review surfaces.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-github-integration.
**PD references:** PD-003, PD-016, PD-020, PD-021, PD-033, PD-036.
**Security Gates:** SG-03, SG-06, SG-09.
**Acceptance IDs:** DG-GH-003, DG-GH-004, DG-GH-006, DG-GH-007, DG-GH-008, DG-GH-009, DG-GH-011, DG-GH-012, DG-GH-013, DG-GH-014, DG-GH-016, DG-GH-017.
**Decision dependencies:** NEEDS_DECISION ND03, ND04, ND06.
**Proposed files/responsibilities:** backend/app/Domain/GitHub/{WebhookInbox,CheckSync,DiffCommentSync}.php.
**Interface / producer–consumer contract:** Authenticated event + opt-in branch/SHA scope + evidence → idempotent job/check/nonblocking comment. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify valid/tampered/replayed/out-of-order events, removed/moved repositories, branch rename, stale SHA and opt-in/branch-denied cases.
- [ ] Observe shared-quota races, provider rate limits and lost responses with persistent dedup; do not invent a GitHub webhook timestamp mechanism.
- [ ] Implement truthful Check/summary synchronization and automatic sanitized nonblocking comments only on valid evidence-grounded diff lines.
- [ ] Verify duplicate comments/remote effects are suppressed and comment exception grants no source/PR-fix authority; denied/quota/blocked work never becomes a successful Check.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T10 — R1 baseline checks and safe browser execution

**Prerequisites:** T03, T06, T08. **Release:** R1 Web only.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-001, PD-004, PD-005, PD-006, PD-008, PD-012, PD-019, PD-022, PD-023, PD-027, PD-029, PD-039.
**Security Gates:** SG-04, SG-06, SG-08.
**Acceptance IDs:** DG-WEB-001, DG-WEB-002, DG-WEB-003, DG-WEB-004, DG-WEB-005, DG-WEB-006, DG-WEB-007.
**Decision dependencies:** Approved behavior; actual subsystem/provider/profile design still requires review.
**Proposed files/responsibilities:** runner/devguard/adapters/web/{baseline,playwright,context}.py.
**Interface / producer–consumer contract:** Vetted React/Vite/vanilla profile + scope → actual deterministic/browser evidence. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify build/lint/existing-test success/failure and absent/infeasible test fixtures independently of browser success.
- [ ] Observe actual Playwright assertion/timeout and isolated cookie/storage/download cleanup failures; unauthorized page instructions cannot expand scope.
- [ ] Implement deterministic baseline and technically feasible risk-targeted browser execution with private revision-bound evidence.
- [ ] Verify safe useful partial results and permitted non-destructive live checks; authenticated broker integration closes in T23, risk planning in T13.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T23 — Brokered test accounts and guided trusted login

**Prerequisites:** T02, T03, T06, T10. **Release:** R1 optional authenticated E2E; reused later.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-runner-isolation; devguard-ai-test-planning.
**PD references:** PD-027, PD-028, PD-029.
**Security Gates:** SG-06, SG-08.
**Acceptance IDs:** DG-SECRET-001, DG-SECRET-002, DG-SECRET-003, DG-SECRET-004, DG-SECRET-005, DG-SECRET-006, DG-SECRET-007.
**Decision dependencies:** NEEDS_DECISION ND05.
**Proposed files/responsibilities:** runner/devguard/auth/{broker_client,trusted_login}.py; agent/devguard/planning/login_setup.py.
**Interface / producer–consumer contract:** Approved test-account reference + sanitized flow → trusted verified authenticated context. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify credential delivery only to trusted login, never AI/arbitrary repo scripts; include revoked/rotated secret, hostile redirect and wrong selector fixtures.
- [ ] Assert CAPTCHA/MFA is not bypassed and missing required setup approval prevents unsafe credential submission.
- [ ] Implement sanitized AI selector/flow guidance with review as needed and trusted Playwright verification.
- [ ] Verify no secret in prompts/errors/logs/reports and continued unauthenticated partial scanning without account/staging; no false authenticated support claim.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T13 — R1 risk planning without AI authority

**Prerequisites:** T03, T06, T07, T08, T10. **Release:** R1 planner; not the R3 evaluation product.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-ai-test-planning.
**PD references:** PD-005, PD-008, PD-027, PD-037, PD-039, PD-041.
**Security Gates:** SG-06, SG-08.
**Acceptance IDs:** DG-AI-001, DG-AI-004, DG-AI-005, DG-WEB-008.
**Decision dependencies:** Approved behavior; actual subsystem/provider/profile design still requires review.
**Proposed files/responsibilities:** agent/devguard/planning/{risk_plan,scope_guard,provider_contract}.py.
**Interface / producer–consumer contract:** Actual findings + capabilities/budget → proposed scoped tests, never new privilege. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify risk-targeted Playwright planning using known deterministic evidence and approved feasible coverage.
- [ ] Observe repository/page injection, malformed/refused/missing provider replies and secret-bearing prompt construction failures.
- [ ] Implement scope/budget constrained planning with sanitized non-secret provider input and trusted action validation.
- [ ] Verify plans execute only via authorized runner/browser contracts and truthful partial/error reporting; R3 rubric/tool evaluation remains T26.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T14 — Grouped Fix Plans and current scoped approvals

**Prerequisites:** T02, T04, T08, T13. **Release:** R1 fix admission.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-ai-fix-approvals.
**PD references:** PD-002, PD-008, PD-017, PD-030.
**Security Gates:** SG-07, SG-09.
**Acceptance IDs:** DG-FIX-001, DG-FIX-002, DG-FIX-003, DG-FIX-004, DG-FIX-010, DG-FIX-011, DG-FIX-012, DG-FIX-013.
**Decision dependencies:** NEEDS_DECISION ND03, ND04.
**Proposed files/responsibilities:** backend/app/Domain/{Approvals/ApprovalPolicy,Fixes/FixPlan,Fixes/Admission}.php.
**Interface / producer–consumer contract:** Root-cause findings + explicit enumerated jobs/budgets → current ApprovalRef and reserved Fix jobs. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify understandable grouped root causes, file/SHA/scope/risks/budget and Fix job/credit disclosure before any source or PR write.
- [ ] Observe missing/stale/revoked/scope-changed approval and pre-approval sandbox-edit failures; local patch creation is not exempt.
- [ ] Implement explicit approval validation and enumerated multi-job dispatch without privilege from an AI recommendation.
- [ ] Verify duplicate Fix dispatch/ledger races and changed file/base rejection; unlisted jobs and expanded budgets require approval rather than implicit reuse.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T15 — Isolated verification, bounded retries and Draft PR handoff

**Prerequisites:** T03, T05, T06, T09, T10, T14. **Release:** R1 Web fixes; later profiles conditional.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-ai-fix-approvals; devguard-github-integration.
**PD references:** PD-002, PD-005, PD-008, PD-017, PD-020, PD-030, PD-031, PD-032.
**Security Gates:** SG-03, SG-06, SG-07, SG-09.
**Acceptance IDs:** DG-FIX-005, DG-FIX-006, DG-FIX-007, DG-FIX-008, DG-FIX-009, DG-FIX-014, DG-FIX-015, DG-FIX-016, DG-FIX-017, DG-FIX-018, DG-FIX-019.
**Decision dependencies:** NEEDS_DECISION ND04.
**Proposed files/responsibilities:** backend/app/Domain/Fixes/{Verification,Retry,Handoff}.php; backend/app/Domain/GitHub/DraftPrPublisher.php.
**Interface / producer–consumer contract:** Approved patch/base/budget + actual scoped checks → verified Draft PR or sanitized limited handoff. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify patched-SHA build/test/regression/secret-screen oracles and explicit distinction between verified Draft PR and separate approved unverified human-review-only Draft PR.
- [ ] Observe failed verification, ceiling exhaustion, stale branch/App access and remote-created-but-response-lost interleavings.
- [ ] Implement bounded included retries, current-scope publication revalidation, security screening and sanitized handoff; no auto-merge or silent unverified publication.
- [ ] Verify actual provider Draft status and one effect; failed executed fix counts under ND03, retries add no Fix debit; R2/API verification is added only after that adapter exists.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T16 — Qualifying trial and monthly commercial lifecycle

**Prerequisites:** T02, T04, T25. **Release:** R1 commercial completeness before public release.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-quota-billing.
**PD references:** PD-010, PD-014, PD-015, PD-016, PD-035, PD-036.
**Security Gates:** SG-09.
**Acceptance IDs:** DG-BILL-001, DG-BILL-002, DG-BILL-003, DG-BILL-004, DG-BILL-005, DG-BILL-006, DG-BILL-007, DG-BILL-008, DG-BILL-009, DG-BILL-010, DG-BILL-011, DG-BILL-012, DG-BILL-013, DG-BILL-014, DG-BILL-015, DG-BILL-016, DG-BILL-017.
**Decision dependencies:** NEEDS_DECISION ND02, ND03, ND11.
**Proposed files/responsibilities:** backend/app/Domain/Billing/{TrialEligibility,Entitlements,ProviderInbox,Reconciliation}.php.
**Interface / producer–consumer contract:** Current authority + confirmed authentic payment/period policy → atomic workspace entitlements. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify one pre-payment real-user qualifying trial, concurrent cross-workspace eligibility and no repeat/BYOK/pay-per-scan paths without guessing anti-abuse ND11.
- [ ] Observe failed/partial/forged/wrong-account payment and duplicate/reordered/concurrent provider-event failures against real persistence.
- [ ] Implement only approved Basic/Pro/Team monthly rules: verified-payment immediate disclosed upgrade, next-cycle downgrade, cancel renewal with paid-term access; exact quota arithmetic waits for ND02/03.
- [ ] Verify shared workspace usage, actual 10/30 Scan counts, no automatic overage and accurate optional upsell; unapproved prices/Team/Fix caps never become defaults.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T17 — Advanced reports and truthful component coverage

**Prerequisites:** T03, T08. **Release:** R1 baseline; integrate every available later producer.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-artifact-privacy; devguard-react-ux.
**PD references:** PD-012, PD-021, PD-029, PD-039, PD-041.
**Security Gates:** SG-10.
**Acceptance IDs:** DG-REPORT-001, DG-REPORT-002, DG-REPORT-005, DG-REPORT-007, DG-REPORT-008, DG-REPORT-009.
**Decision dependencies:** Approved behavior; actual subsystem/provider/profile design still requires review.
**Proposed files/responsibilities:** backend/app/Domain/Reports/{EvidenceValidator,Coverage,History}.php; frontend/src/features/reports/ReportView.tsx.
**Interface / producer–consumer contract:** Actual run/component/SHA evidence → scoped CoverageReport with severity/history/diagnosis/fix status. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify provenance, severity/timeline/history/run comparison, remediation and actual fix/PR status from controlled producer results.
- [ ] Observe partial/unsupported/missing/corrupt/wrong-revision evidence and unsafe rendered repository content before aggregating results.
- [ ] Implement evidence-backed reports/downloads and disclose incomparable runs or absent coverage instead of promoting overall success.
- [ ] Verify end-to-end private capture/report paths with each available producer; do not wait for R2–R5 implementation to complete R1 reports, and do not claim those future layers covered.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T18 — Backup, recovery, containment and incident operations

**Prerequisites:** T08, T09, T15, T16, T17. **Release:** R1 operational gates; rerun later profiles.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-observability-release.
**PD references:** PD-010, PD-012, PD-013, PD-019, PD-022, PD-023, PD-039.
**Security Gates:** SG-02, SG-04, SG-11, SG-12.
**Acceptance IDs:** DG-REC-001, DG-REC-002, DG-REC-003, DG-REC-004, DG-REC-005, DG-REC-006, DG-REC-007, DG-REC-008, DG-REC-009, DG-REC-010, DG-REC-011, DG-REC-012.
**Decision dependencies:** NEEDS_DECISION ND12, ND13.
**Proposed files/responsibilities:** backend/app/Domain/Recovery/{JobReconciler,ArtifactReconciler,KillSwitches}.php; runner/devguard/isolation/orphans.py; future operations runbooks.
**Interface / producer–consumer contract:** Durable scoped state + approved recovery/containment policy → safe reconciled effects and measured drill evidence. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify queue/database/cache/upload/runner orphan faults and isolated restore/migration rollback fixtures, including revoked permissions and tenant markers.
- [ ] Observe no-tenant-unsafe-fallback, no-double-ledger/write and quarantine-before-reuse assertions under controlled crash/restart.
- [ ] Implement approved recovery and independent scans/fixes/GitHub-write/runner emergency controls; define active-work containment through ND13.
- [ ] Exercise monitoring/authorized alerts, six-phase incident runbook and selected RPO/RTO restore/rollback; record actual evidence rather than adopting proposed 1h/4h.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T19 — Zero-configuration UI, real progress and informed approval

**Prerequisites:** T05, T08, T14, T17, T23. **Release:** R1 Dashboard; later platform views.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-react-ux.
**PD references:** PD-007, PD-008, PD-010, PD-012, PD-019, PD-029, PD-030.
**Security Gates:** SG-02, SG-07.
**Acceptance IDs:** DG-UX-001, DG-UX-002, DG-UX-003, DG-UX-004, DG-UX-005, DG-UX-006, DG-UX-007, DG-UX-008.
**Decision dependencies:** NEEDS_DECISION ND09.
**Proposed files/responsibilities:** frontend/src/features/{onboarding,jobs,approvals,reports}/.
**Interface / producer–consumer contract:** Actual server state/current permission → truthful permitted UI interactions. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify no-staging/login useful partial onboarding, real reconnect/stage displays and denied stale-browser actions.
- [ ] Observe all plan disclosures before approve/reject and that no percentage/completion is simulated without measured data.
- [ ] Implement views and action requests from actual authorized state, including empty/large/Unicode external data handling without falsified results.
- [ ] Verify browser/server integration; keep UX-004 supplementary accessibility ND09 pending and do not fabricate a PRD compliance standard.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T20 — Dashboard/Email priority notifications

**Prerequisites:** T02, T03, T17, T18. **Release:** R1 approved channels only.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-observability-release.
**PD references:** PD-010, PD-011, PD-019, PD-034.
**Security Gates:** SG-02.
**Acceptance IDs:** DG-NOTIFY-001, DG-NOTIFY-002, DG-NOTIFY-003, DG-NOTIFY-004, DG-NOTIFY-005, DG-NOTIFY-006, DG-NOTIFY-007.
**Decision dependencies:** NEEDS_DECISION ND14.
**Proposed files/responsibilities:** backend/app/Domain/Notifications/{RecipientPolicy,Priority,Deduplication,Delivery}.php.
**Interface / producer–consumer contract:** Actual event/current recipient preferences → permitted deduplicated Dashboard/Email intent. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify critical/important/info routing, preferences and explicit no-Telegram/no-routine-success-email behavior.
- [ ] Observe stale recipient/revoked membership, response-lost retry and channel outage without repeating job/financial actions.
- [ ] Implement current delivery-time permission/content checks and independent truthful notification state using approved channel policy.
- [ ] Verify channel integration and private links/content without changing job outcome because Email failed; keep exact routing/retry ND14 explicit.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T21 — Shared adapter/capability contract and unsupported denial

**Prerequisites:** T06, T07, T08. **Release:** R1 contract; R2–R5 instantiate native lifecycle.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-022, PD-023, PD-041, PD-042.
**Security Gates:** SG-04.
**Acceptance IDs:** DG-PLAT-001, DG-PLAT-005, DG-PLAT-006, DG-PLAT-007.
**Decision dependencies:** NEEDS_DECISION ND07.
**Proposed files/responsibilities:** runner/devguard/adapters/contracts.py; backend/app/Domain/Runners/CapabilityPolicy.php.
**Interface / producer–consumer contract:** Trusted compatible profile → detect/plan/validate/execute/collect/cleanup and explicit results. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify the shared lifecycle, version/support metadata, independent Scan/Fix support and fail-closed unavailable/unsupported runner response.
- [ ] Observe missing validation/cleanup, device-loss and residual-state contract failures with isolated controlled adapters.
- [ ] Implement trusted adapter selection and capability/result schema without an LLM choosing unrestricted execution.
- [ ] Verify core contract at R1 and rerun actual device/session assertions in T27–T29; contract fixture success does not certify a real native runner.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T24 — Measured cost, capacity and sustainable pricing

**Prerequisites:** T08, T15, T16, T18. **Release:** R1 viability gate; repeat per later platform.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-quota-billing; devguard-observability-release.
**PD references:** PD-013, PD-016, PD-017, PD-033, PD-038.
**Security Gates:** SG-09.
**Acceptance IDs:** DG-COST-001, DG-COST-002, DG-COST-003, DG-COST-004, DG-COST-005, DG-COST-006.
**Decision dependencies:** NEEDS_DECISION ND02, ND04.
**Proposed files/responsibilities:** backend/app/Domain/Costs/{Usage,Aggregation,BudgetGate}.php; future measured operations reports.
**Interface / producer–consumer contract:** Actual scan/fix/trial/overhead observations → cost/tail/scenario and margin evidence. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify actual tokens/retries/sandbox/browser costs separately for Scan/Fix plus free-trial and fixed hosting/data overhead.
- [ ] Observe trusted budget-gate failures and distinguish measured observations from stress simulation or illustrative prices.
- [ ] Implement cost provenance, P50/P95/p99, expected/base/stress reporting and configurable approved budget/margin alerts.
- [ ] Run only later-authorized isolated load/cost experiments and financial review; do not promote proposed prices/caps/60-40-20 targets or vendor cost examples into approved policy.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T22 — Evidence-backed security and release review

**Prerequisites:** T02, T03, T04, T05, T06, T07, T08, T09, T10, T13, T14, T15, T16, T17, T18, T19, T20, T21, T23, T24, T25. **Release:** R1 review; repeat for each release's available scope.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-observability-release; devguard-acceptance-specs.
**PD references:** PD-003, PD-005, PD-008, PD-010, PD-012, PD-030, PD-035, PD-038, PD-039, PD-040, PD-041, PD-042.
**Security Gates:** SG-01, SG-02, SG-03, SG-04, SG-05, SG-06, SG-07, SG-08, SG-09, SG-10, SG-11, SG-12.
**Acceptance IDs:** DG-GATE-001, DG-GATE-002, DG-GATE-003, DG-GATE-004, DG-PLAT-015, DG-GATE-005, DG-GATE-006, DG-GATE-007, DG-GATE-008, DG-GATE-009, DG-GATE-010, DG-GATE-011, DG-GATE-012.
**Decision dependencies:** NEEDS_DECISION ND01, ND02, ND03, ND06, ND15.
**Proposed files/responsibilities:** release/acceptance/{security_gates,evidence_index,platform_claims,go_no_go}.md or later approved automation.
**Interface / producer–consumer contract:** Exact release/profile/source/policy evidence → explicit blocked or reviewed Go/No-Go, never deployment authority. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify fail-closed omission/stale-evidence checks for every SG-01–SG-12 area and every approved launch clause; all P0 mappings require actual future results.
- [ ] Observe missing security control, exploitable critical/high finding, missing pilot/runbook and unsupported readiness claims blocking launch.
- [ ] Verify security-first GitHub→Scan→Report, Approval→Fix→Draft PR and commercial hardening slices, with each relevant producer integrated.
- [ ] Review actual cost/financial/legal/provider/privacy validation, limited authorized pilot and explicit Go/No-Go; do not count specified/blocked/unsupported cases as passes or auto-deploy/merge.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T11 — R2 vetted Backend/API execution

**Prerequisites:** T06, T08, T22, T23. **Release:** R2; after R1 acceptance.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-006, PD-027, PD-041, PD-042.
**Security Gates:** SG-04, SG-06, SG-07.
**Acceptance IDs:** DG-API-001, DG-API-002, DG-API-003, DG-API-004, DG-API-005, DG-API-006, DG-API-007.
**Decision dependencies:** NEEDS_DECISION ND01, ND07.
**Proposed files/responsibilities:** runner/devguard/adapters/api/{profiles,executor,fixtures,contracts}.py.
**Interface / producer–consumer contract:** Approved Laravel/Node/Python profile + scoped identity/test DB → observed API/role/contract outcomes. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify vetted runtime/version profiles, valid/malformed/boundary/role-negative and transport/service-start failure fixtures.
- [ ] Observe authenticated object/role denial and production side-effect policy independent of HTTP method, using brokered fixture credentials.
- [ ] Implement isolated API/service/test-database lifecycle and actual contract/integration assertions without trusted-host execution.
- [ ] Verify cleanup, evidence and tenant boundaries; integrate R2 reports/recovery/cost then repeat T22 before enabling the profile.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T12 — R2 bounded Web+API composite

**Prerequisites:** T04, T10, T11. **Release:** R2 Full-Stack after API.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-006, PD-016, PD-029, PD-041, PD-042.
**Security Gates:** SG-04.
**Acceptance IDs:** DG-FULL-001, DG-FULL-002, DG-FULL-003, DG-FULL-004, DG-FULL-005.
**Decision dependencies:** Approved behavior; actual subsystem/provider/profile design still requires review.
**Proposed files/responsibilities:** runner/devguard/adapters/full_stack/{planner,correlation,fixtures,cleanup}.py.
**Interface / producer–consumer contract:** Same-scope Web/API/services/test data → correlated unified result within one bounded Scan. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify seeded cross-layer journeys, root-cause correlation and missing-backend partial coverage with exact run/environment identities.
- [ ] Observe concurrent tenant fixture isolation and interrupted authorized data mutations/cleanup without reaching production.
- [ ] Implement composite scheduling/correlation under one Scan credit and explicit resource/time/AI/browser ceilings.
- [ ] Verify actual cross-service failures, private data cleanup and report limitations; rerun T17/T18/T24/T22 for R2 evidence.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T26 — R3 agent/chatbot/RAG evaluation adapter

**Prerequisites:** T06, T08, T13, T21, T24, T12. **Release:** R3 after R2 acceptance review.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-ai-test-planning; devguard-platform-adapters.
**PD references:** PD-016, PD-038, PD-041, PD-042.
**Security Gates:** SG-04, SG-06, SG-08.
**Acceptance IDs:** DG-AI-002, DG-AI-003, DG-AI-006, DG-AI-007, DG-AI-008, DG-AI-009, DG-AI-010, DG-AI-011, DG-AI-012, DG-AI-013.
**Decision dependencies:** NEEDS_DECISION ND03, ND04, ND08.
**Proposed files/responsibilities:** agent/devguard/evaluation/{corpus,rubrics,tools,grounding,regression}.py; runner/devguard/adapters/ai/.
**Interface / producer–consumer contract:** Versioned app/corpus/rubric + brokered/mock tools → measured deterministic/rubric result and cost provenance. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify agent/chatbot/RAG tool correctness, grounding/hallucination, regression, injection, uncertainty and failed-provider corpora with approved ND08 oracles.
- [ ] Observe tool side-effect/tenant/secret/budget denials and interrupted evaluation without fabricated metric results.
- [ ] Implement isolated evaluation and brokered/mock tool contracts, keeping deterministic assertions separate from rubric opinions.
- [ ] Verify actual observations, version/configuration provenance and token/cost limits; integrate reports/recovery and repeat relevant gates before R3 availability.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T27 — R4 Android native profiles

**Prerequisites:** T06, T21, T26. **Release:** R4 Android first; after R3 acceptance review.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-041, PD-042.
**Security Gates:** SG-04.
**Acceptance IDs:** DG-PLAT-002, DG-PLAT-008, DG-PLAT-009.
**Decision dependencies:** NEEDS_DECISION ND07.
**Proposed files/responsibilities:** runner/devguard/adapters/android/{build,ui,emulator,lifecycle}.py.
**Interface / producer–consumer contract:** Vetted Android app/version + clean managed isolated emulator → actual native build/UI evidence. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify approved Android build/UI success/failure profiles and permission/host/cross-app probing fixtures.
- [ ] Observe no residual tenant/device state and no unsafe substitute runner when device is lost or unsupported.
- [ ] Implement only vetted Android emulator execution and OS-specific containment, collection and cleanup.
- [ ] Verify actual compatible native runner, costs/recovery/contracts and repeat SG controls via T22 before enabling Android; discovery alone is not support.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T28 — R4 compliant iOS native profiles

**Prerequisites:** T06, T21, T27. **Release:** R4 iOS after Android acceptance review.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-041, PD-042.
**Security Gates:** Relevant gates inherited from integration scope; no additional invented gate.
**Acceptance IDs:** DG-PLAT-003, DG-PLAT-010, DG-PLAT-011.
**Decision dependencies:** NEEDS_DECISION ND06, ND07.
**Proposed files/responsibilities:** runner/devguard/adapters/ios/{build,xctest,ui,simulator,lifecycle}.py.
**Interface / producer–consumer contract:** Vetted provider/macOS/Xcode/simulator profile → actual compliant isolated iOS evidence. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify compatibility, security/operations/licensing/provider validation before claiming any available iOS runner.
- [ ] Observe build/XCTest/UI failure, incompatible profile, session loss and private state cleanup using iOS-specific fixtures.
- [ ] Implement only separately approved compliant managed macOS/Xcode simulator execution with the shared contract and security controls.
- [ ] Verify actual provider/OS/version evidence and T22 gates; Android or browser results cannot substitute for iOS verification.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

### T29 — R5 Desktop OS-specific profiles

**Prerequisites:** T06, T21, T28. **Release:** R5 after mobile acceptance review.
**Skills:** `devguard-acceptance-specs` and future Superpowers TDD/verification/review; planned specialist guidance: devguard-platform-adapters.
**PD references:** PD-022, PD-023, PD-041, PD-042.
**Security Gates:** SG-04, SG-05.
**Acceptance IDs:** DG-PLAT-004, DG-PLAT-012, DG-PLAT-013, DG-PLAT-014.
**Decision dependencies:** NEEDS_DECISION ND07.
**Proposed files/responsibilities:** runner/devguard/adapters/desktop/{profiles,gui,filesystem,process,network,cleanup}.py.
**Interface / producer–consumer contract:** Vetted Electron/Tauri then native OS profile → actual per-OS GUI/build/assertion results. T01 pins exact record schema, method signatures and approved error/idempotency semantics before consumers implement this boundary.

- [ ] Specify initial Electron/Tauri and later compatible Windows/macOS/Linux native entries separately; versions remain ND07.
- [ ] Observe OS-specific filesystem/process/network escape, GUI failure/crash and cross-tenant residual state assertions.
- [ ] Implement isolated matching OS runners and clean GUI session lifecycle without trusted-host/native fallback.
- [ ] Verify each enabled OS profile independently with cost/recovery/cleanup and T22 gate evidence; one OS never certifies all Desktop support.

**Completion evidence:** Inspect the named case assertions against actual results, including documented cross-task integration closure. Require the common cycle's regression/review and safe integration evidence during authorized execution. A specified assertion or mock-only boundary is not a passing end-to-end result.

## 6. PD/SG-to-task traceability index

This is the implementation side of the master plan's Requirements Traceability Matrix. The master table lists every explicit case ID and source clause; each ID has exactly one canonical owner above. Other tasks can run a regression of that case without creating a duplicate case/owner. Mappings were checked as documentation; **none is evidence of an implemented requirement**.

| Requirement | Implementation owners | Exact acceptance IDs | Open decision dependencies |
|---|---|---|---|
| PD-001 | T08, T10 | DG-JOB-001, DG-WEB-001, DG-JOB-009, DG-JOB-010 | ND10 |
| PD-002 | T14, T15 | DG-FIX-001, DG-FIX-005, DG-FIX-007, DG-FIX-014, DG-FIX-018 | Design/evidence pending |
| PD-003 | T08, T09, T22 | DG-GH-003, DG-GH-004, DG-GH-006, DG-JOB-001, DG-JOB-002, DG-JOB-003, DG-GH-007, DG-GH-008, DG-GH-009, DG-GH-017, DG-JOB-010, DG-GATE-010 | ND03, ND06, ND10 |
| PD-004 | T07, T10 | DG-DISC-001, DG-WEB-001, DG-DISC-008, DG-WEB-006 | Design/evidence pending |
| PD-005 | T10, T13, T15, T22 | DG-WEB-001, DG-WEB-002, DG-WEB-003, DG-AI-004, DG-FIX-005, DG-WEB-006, DG-WEB-007, DG-WEB-008, DG-FIX-014, DG-GATE-010 | Design/evidence pending |
| PD-006 | T06, T08, T10, T11, T12 | DG-RUN-004, DG-RUN-007, DG-RUN-008, DG-JOB-002, DG-WEB-005, DG-API-003, DG-FULL-003, DG-JOB-009, DG-API-006, DG-FULL-005 | Design/evidence pending |
| PD-007 | T07, T19 | DG-DISC-001, DG-DISC-004, DG-DISC-005, DG-UX-001, DG-DISC-006, DG-DISC-007, DG-UX-006 | Design/evidence pending |
| PD-008 | T02, T06, T10, T13, T14, T15, T19, T22 | DG-IAM-005, DG-RUN-008, DG-WEB-005, DG-AI-001, DG-FIX-001, DG-FIX-002, DG-FIX-003, DG-FIX-006, DG-FIX-007, DG-FIX-008, DG-UX-003, DG-FIX-010, DG-FIX-011, DG-FIX-012, DG-FIX-013, DG-FIX-017, DG-FIX-019, DG-UX-007, DG-GATE-011 | ND03, ND04 |
| PD-009 | T02, T05 | DG-IAM-007, DG-GH-001, DG-GH-002, DG-GH-005, DG-IAM-009, DG-IAM-010, DG-IAM-011, DG-IAM-012, DG-IAM-013, DG-GH-010 | ND06 |
| PD-010 | T02, T03, T04, T05, T08, T16, T18, T19, T20, T22 | DG-IAM-001, DG-IAM-002, DG-IAM-003, DG-IAM-004, DG-IAM-005, DG-IAM-006, DG-IAM-007, DG-IAM-008, DG-GH-002, DG-CREDIT-008, DG-REPORT-003, DG-BILL-001, DG-REC-002, DG-REC-006, DG-UX-003, DG-NOTIFY-001, DG-IAM-014, DG-IAM-015, DG-IAM-016, DG-JOB-008, DG-NOTIFY-007, DG-GATE-012 | ND01, ND02, ND03, ND14 |
| PD-011 | T20 | DG-NOTIFY-001, DG-NOTIFY-002, DG-NOTIFY-003, DG-NOTIFY-004 | Design/evidence pending |
| PD-012 | T03, T10, T17, T18, T19, T22 | DG-WEB-002, DG-REPORT-001, DG-REPORT-002, DG-REPORT-003, DG-REPORT-004, DG-REPORT-005, DG-REPORT-007, DG-REC-003, DG-UX-005, DG-GATE-002, DG-REPORT-008, DG-REPORT-009, DG-REPORT-012, DG-REPORT-013, DG-GATE-010 | ND05 |
| PD-013 | T02, T06, T18, T24 | DG-IAM-001, DG-IAM-003, DG-IAM-004, DG-RUN-003, DG-RUN-006, DG-REC-002, DG-IAM-016, DG-RUN-016, DG-COST-006 | ND04 |
| PD-014 | T16 | DG-BILL-007, DG-BILL-009, DG-BILL-010 | ND02, ND11 |
| PD-015 | T04, T16 | DG-CREDIT-001, DG-CREDIT-007, DG-BILL-002, DG-BILL-005, DG-BILL-006, DG-CREDIT-009, DG-CREDIT-014, DG-BILL-007, DG-BILL-008, DG-BILL-009, DG-BILL-010, DG-BILL-015, DG-BILL-016, DG-BILL-017 | ND02, ND03, ND11 |
| PD-016 | T04, T06, T08, T09, T12, T16, T24, T26 | DG-GH-004, DG-RUN-006, DG-JOB-001, DG-JOB-002, DG-JOB-003, DG-JOB-005, DG-CREDIT-001, DG-CREDIT-003, DG-CREDIT-005, DG-CREDIT-006, DG-CREDIT-008, DG-BILL-005, DG-GH-008, DG-RUN-016, DG-JOB-011, DG-CREDIT-009, DG-CREDIT-010, DG-CREDIT-011, DG-CREDIT-013, DG-CREDIT-014, DG-FULL-004, DG-AI-011, DG-COST-004 | ND02, ND03, ND04 |
| PD-017 | T04, T14, T15, T24 | DG-CREDIT-002, DG-CREDIT-004, DG-CREDIT-005, DG-CREDIT-006, DG-CREDIT-008, DG-FIX-004, DG-CREDIT-012, DG-CREDIT-013, DG-CREDIT-014, DG-FIX-011, DG-FIX-015, DG-COST-004 | ND03, ND04 |
| PD-018 | T03 | DG-REPORT-003, DG-REPORT-006, DG-REPORT-010, DG-REPORT-011, DG-REPORT-012, DG-REPORT-013 | ND05 |
| PD-019 | T08, T10, T18, T19, T20 | DG-JOB-004, DG-JOB-005, DG-JOB-006, DG-WEB-003, DG-REC-001, DG-REC-003, DG-REC-005, DG-UX-002, DG-UX-005, DG-NOTIFY-003, DG-JOB-007, DG-JOB-008, DG-JOB-011, DG-UX-008 | ND03 |
| PD-020 | T05, T09, T15 | DG-GH-003, DG-GH-005, DG-GH-006, DG-FIX-008, DG-GH-009, DG-GH-010, DG-GH-011, DG-GH-012, DG-GH-013, DG-GH-014, DG-GH-017, DG-FIX-018, DG-FIX-019 | ND06 |
| PD-021 | T09, T17 | DG-REPORT-007, DG-GH-012, DG-GH-013, DG-GH-014 | Design/evidence pending |
| PD-022 | T06, T07, T10, T18, T21, T29 | DG-DISC-004, DG-RUN-001, DG-RUN-009, DG-RUN-010, DG-WEB-004, DG-PLAT-006, DG-REC-004, DG-RUN-014, DG-PLAT-014 | ND07 |
| PD-023 | T06, T07, T10, T18, T21, T29 | DG-DISC-004, DG-RUN-001, DG-RUN-003, DG-RUN-005, DG-RUN-006, DG-RUN-009, DG-RUN-010, DG-WEB-004, DG-PLAT-005, DG-REC-004, DG-RUN-015, DG-RUN-016, DG-PLAT-013 | ND04, ND07 |
| PD-024 | T06 | DG-RUN-002, DG-RUN-011, DG-RUN-012 | ND06 |
| PD-025 | T06, T25 | DG-RUN-002, DG-RUN-011, DG-HOST-003 | ND06, ND15 |
| PD-026 | T06 | DG-RUN-004, DG-RUN-013, DG-RUN-017, DG-RUN-018 | ND04 |
| PD-027 | T03, T06, T10, T11, T13, T23 | DG-RUN-005, DG-WEB-004, DG-AI-005, DG-REPORT-004, DG-API-006, DG-SECRET-001, DG-SECRET-002, DG-SECRET-003, DG-SECRET-004, DG-SECRET-005, DG-SECRET-006, DG-SECRET-007, DG-RUN-018 | ND05 |
| PD-028 | T23 | DG-SECRET-004, DG-SECRET-005, DG-SECRET-006 | ND05 |
| PD-029 | T07, T10, T12, T17, T19, T23 | DG-DISC-001, DG-DISC-002, DG-DISC-005, DG-FULL-002, DG-REPORT-002, DG-UX-001, DG-DISC-006, DG-DISC-007, DG-WEB-007, DG-SECRET-007, DG-UX-006 | Design/evidence pending |
| PD-030 | T14, T15, T19, T22 | DG-FIX-001, DG-FIX-002, DG-FIX-003, DG-FIX-004, DG-FIX-005, DG-FIX-010, DG-FIX-011, DG-FIX-012, DG-FIX-013, DG-FIX-014, DG-FIX-018, DG-UX-007, DG-GATE-011 | ND03, ND04 |
| PD-031 | T15 | DG-FIX-008, DG-FIX-009, DG-FIX-015, DG-FIX-016 | ND04 |
| PD-032 | T15 | DG-FIX-006, DG-FIX-007, DG-FIX-009, DG-FIX-016, DG-FIX-017 | Design/evidence pending |
| PD-033 | T05, T09, T24 | DG-GH-015, DG-GH-016, DG-COST-006 | ND04 |
| PD-034 | T20 | DG-NOTIFY-001, DG-NOTIFY-002, DG-NOTIFY-003, DG-NOTIFY-004, DG-NOTIFY-005, DG-NOTIFY-006, DG-NOTIFY-007 | ND14 |
| PD-035 | T03, T16, T22 | DG-BILL-001, DG-BILL-002, DG-BILL-003, DG-BILL-004, DG-BILL-005, DG-REPORT-011, DG-BILL-011, DG-BILL-012, DG-BILL-013, DG-BILL-014, DG-BILL-015, DG-GATE-012 | ND01, ND02, ND03, ND05 |
| PD-036 | T04, T09, T16 | DG-CREDIT-007, DG-BILL-006, DG-GH-011, DG-GH-016, DG-CREDIT-015, DG-BILL-016 | ND03, ND04 |
| PD-037 | T06, T13, T25 | DG-RUN-001, DG-RUN-002, DG-RUN-003, DG-AI-005, DG-HOST-001, DG-HOST-002, DG-HOST-003 | ND06, ND15 |
| PD-038 | T22, T24, T26 | DG-AI-010, DG-AI-011, DG-GATE-009, DG-COST-001, DG-COST-002, DG-COST-003, DG-COST-004, DG-COST-005, DG-COST-006 | ND02, ND04, ND06, ND08, ND15 |
| PD-039 | T02, T06, T10, T13, T17, T18, T22, T25 | DG-IAM-005, DG-WEB-005, DG-AI-001, DG-REPORT-005, DG-REC-001, DG-REC-005, DG-REC-006, DG-GATE-001, DG-GATE-002, DG-GATE-003, DG-RUN-015, DG-PLAT-015, DG-REC-007, DG-REC-008, DG-REC-009, DG-REC-010, DG-REC-011, DG-REC-012, DG-GATE-005, DG-GATE-006, DG-GATE-009, DG-HOST-003, DG-RUN-018 | ND02, ND06, ND12, ND13, ND15 |
| PD-040 | T22 | DG-GATE-007, DG-GATE-010, DG-GATE-011, DG-GATE-012 | ND01, ND02, ND03 |
| PD-041 | T04, T06, T07, T11, T12, T13, T17, T21, T22, T26, T27, T28, T29 | DG-DISC-001, DG-DISC-002, DG-DISC-003, DG-DISC-005, DG-API-001, DG-API-002, DG-API-004, DG-FULL-001, DG-FULL-002, DG-FULL-003, DG-AI-002, DG-AI-003, DG-AI-004, DG-PLAT-001, DG-PLAT-002, DG-PLAT-003, DG-PLAT-004, DG-PLAT-005, DG-PLAT-006, DG-REPORT-002, DG-GATE-002, DG-GATE-003, DG-GATE-004, DG-DISC-008, DG-DISC-009, DG-DISC-010, DG-DISC-011, DG-RUN-012, DG-CREDIT-011, DG-API-005, DG-API-006, DG-API-007, DG-FULL-004, DG-FULL-005, DG-AI-006, DG-AI-007, DG-AI-008, DG-AI-009, DG-AI-010, DG-AI-011, DG-AI-012, DG-AI-013, DG-PLAT-007, DG-PLAT-008, DG-PLAT-009, DG-PLAT-010, DG-PLAT-011, DG-PLAT-012, DG-PLAT-013, DG-PLAT-014, DG-PLAT-015, DG-GATE-008 | ND01, ND03, ND04, ND06, ND07, ND08 |
| PD-042 | T07, T11, T12, T21, T22, T26, T27, T28, T29 | DG-DISC-003, DG-API-001, DG-API-002, DG-FULL-001, DG-AI-002, DG-PLAT-001, DG-PLAT-002, DG-PLAT-003, DG-PLAT-004, DG-GATE-004, DG-DISC-008, DG-DISC-009, DG-API-005, DG-AI-006, DG-PLAT-008, DG-PLAT-009, DG-PLAT-010, DG-PLAT-011, DG-PLAT-012, DG-PLAT-015, DG-GATE-008 | ND01, ND06, ND07, ND08 |

| Security Gate | Assurance owners | Direct assurance case IDs (plus DG-GATE-001 all-gate fail-closed review) |
|---|---|---|
| SG-01 | T02 | DG-IAM-007, DG-IAM-009, DG-IAM-010, DG-IAM-011, DG-IAM-012, DG-IAM-013 |
| SG-02 | T02, T08, T18, T19, T20, T22 | DG-IAM-001, DG-IAM-002, DG-IAM-003, DG-IAM-004, DG-IAM-005, DG-IAM-006, DG-IAM-007, DG-IAM-008, DG-JOB-001, DG-JOB-002, DG-REC-002, DG-UX-003, DG-NOTIFY-001, DG-IAM-014, DG-IAM-015, DG-IAM-016, DG-JOB-008, DG-NOTIFY-007, DG-GATE-010, DG-GATE-012 |
| SG-03 | T05, T09, T15, T22 | DG-GH-001, DG-GH-002, DG-GH-003, DG-GH-004, DG-GH-005, DG-GH-006, DG-FIX-008, DG-GH-007, DG-GH-008, DG-GH-009, DG-GH-010, DG-GH-011, DG-GH-012, DG-GH-013, DG-GH-014, DG-GH-015, DG-GH-016, DG-GH-017, DG-FIX-018, DG-FIX-019, DG-GATE-010, DG-GATE-011 |
| SG-04 | T06, T07, T10, T11, T12, T18, T21, T22, T25, T26, T27, T29 | DG-DISC-004, DG-RUN-001, DG-RUN-002, DG-RUN-003, DG-RUN-005, DG-RUN-006, DG-RUN-009, DG-RUN-010, DG-WEB-004, DG-FULL-003, DG-PLAT-005, DG-PLAT-006, DG-REC-004, DG-RUN-011, DG-RUN-012, DG-RUN-014, DG-RUN-015, DG-RUN-016, DG-API-006, DG-FULL-005, DG-AI-012, DG-PLAT-009, DG-PLAT-013, DG-HOST-001, DG-HOST-002, DG-GATE-010 |
| SG-05 | T06, T29 | DG-RUN-004, DG-RUN-013, DG-PLAT-013, DG-RUN-017, DG-RUN-018 |
| SG-06 | T03, T06, T09, T10, T11, T13, T15, T23, T25, T26 | DG-RUN-005, DG-WEB-004, DG-AI-005, DG-FIX-009, DG-REPORT-004, DG-GH-012, DG-API-006, DG-SECRET-001, DG-SECRET-002, DG-SECRET-003, DG-SECRET-004, DG-SECRET-005, DG-SECRET-006, DG-SECRET-007, DG-AI-012, DG-FIX-014, DG-FIX-016, DG-HOST-002, DG-RUN-018 |
| SG-07 | T06, T11, T14, T15, T19, T22 | DG-RUN-007, DG-RUN-008, DG-API-003, DG-FIX-001, DG-FIX-002, DG-FIX-003, DG-FIX-004, DG-FIX-006, DG-FIX-007, DG-FIX-008, DG-FIX-009, DG-UX-003, DG-FIX-011, DG-FIX-012, DG-FIX-013, DG-FIX-014, DG-FIX-015, DG-FIX-016, DG-FIX-017, DG-FIX-018, DG-FIX-019, DG-GATE-011 |
| SG-08 | T02, T06, T10, T13, T23, T26 | DG-IAM-005, DG-WEB-005, DG-AI-001, DG-SECRET-004, DG-AI-007, DG-AI-009, DG-RUN-018 |
| SG-09 | T04, T06, T08, T09, T14, T15, T16, T22, T24 | DG-JOB-003, DG-CREDIT-001, DG-CREDIT-002, DG-CREDIT-003, DG-CREDIT-004, DG-CREDIT-005, DG-CREDIT-006, DG-CREDIT-007, DG-CREDIT-008, DG-BILL-001, DG-BILL-002, DG-BILL-003, DG-BILL-004, DG-BILL-005, DG-BILL-006, DG-GH-008, DG-RUN-016, DG-FIX-011, DG-FIX-015, DG-BILL-008, DG-BILL-011, DG-BILL-012, DG-BILL-015, DG-COST-004, DG-BILL-017, DG-GATE-010, DG-GATE-011, DG-GATE-012 |
| SG-10 | T03, T17 | DG-REPORT-003, DG-REPORT-004, DG-REPORT-005, DG-REPORT-006, DG-REPORT-007, DG-REPORT-010, DG-REPORT-011, DG-REPORT-012, DG-REPORT-013 |
| SG-11 | T18 | DG-REC-001, DG-REC-003, DG-REC-004, DG-REC-006, DG-REC-007, DG-REC-008 |
| SG-12 | T18 | DG-REC-005, DG-REC-009, DG-REC-010, DG-REC-011, DG-REC-012 |

## 7. Remaining decisions and handoff

The source is present and all 42 decisions/12 security areas have written traceability; the previous missing-source blocker is resolved. The outstanding ND01–ND15 register in the master plan remains binding on task readiness:

| Decision | Needed before |
|---|---|
| ND01 exact role grants | Role-specific T02/T14/T16 and API authorization acceptance |
| ND02 commercial values/upgrade arithmetic/margins | T16 exact entitlements/upgrade charging and T24 financial validation |
| ND03 settlement/cancel/reset/timezone | T04/T08/T15/T16 financial terminal/boundary cases |
| ND04 run/retry/fair-use budgets | T06 execution budgets, T14/T15 bounded jobs, T24 cost limits |
| ND05 retention/links/encryption/broker lifecycle | T03/T23 artifact/secret lifecycle oracles |
| ND06 provider/least-privilege contracts | T25/T05/T06 actual provider integration, iOS and billing eligibility |
| ND07 compatible versions/runner matrix | T10/T11/T21/T27–T29 specific profile availability |
| ND08 evaluation corpora/rubrics/thresholds | T26 actual AI Applications quality oracle |
| ND09 supplementary accessibility standard | T19 UX-004 only; not an invented PRD release gate |
| ND10 deployed-SHA sequencing | T08 live-target revision-attribution claim |
| ND11 privacy-safe real-user trial eligibility | T16 anti-abuse/race oracle |
| ND12 selected RPO/RTO and rollback design | T18 recovery acceptance |
| ND13 active-work kill-switch/resume policy | T18 incident containment/recovery |
| ND14 priority/preference/delivery semantics | T20 detailed notification routing |
| ND15 legal/geography/subprocessor/provider validation | T25/T16 billing and T22 public launch |

No unresolved proposal is silently resolved by a file/interface name or a generic “approved policy” phrase. Refine the linked cases when decisions close, preserve their IDs and review any changed observable behavior. Superseded local L-/C- requirement aliases are replaced with the authoritative PD/SG references.

The updated plan follows PD-040 security-first vertical slices rather than a sequence that postpones security or waits for every future adapter. It names all current catalog owners and records deferred integration explicitly. Independent specialist/security/finance review and actual implementation/evidence remain future work; this self-review does not approve a release or validate the draft custom skill.

The handoff is the two revised Markdown documents. Obtain review of subsystem specifications and separate implementation/test authorization before any engineering execution, dependency installation, repository initialization or deployment. No action from that future workflow was performed for this request.
