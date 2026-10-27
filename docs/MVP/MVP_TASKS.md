# DevGuardAI MVP implementation task catalog

> For future agentic work: Codex plans and reviews; Antigravity implements one explicitly approved task at a time through direct CLI. Use applicable Superpowers execution/TDD skills only after authorization; the specified Antigravity workflow takes precedence over automatic subagent implementation. Nothing in this catalog authorizes execution.

**Goal:** deliver the independent end-to-end Web evaluation MVP in MVP_PLAN.md.
**Architecture:** Laravel policy and durable workflow; private advisory FastAPI; React JavaScript; real Playwright and customer commands exclusively in vetted external isolation; scoped trusted GitHub publisher.
**Specification:** [MVP_PLAN.md](MVP_PLAN.md), [MVP_ARCHITECTURE.md](MVP_ARCHITECTURE.md), canonical PRD and approved ND01/ND13.
**Count:** 32 tasks, MVP-01–MVP-32. All implementation statuses: NOT STARTED / NOT AUTHORIZED. Numbers are identifiers; dependencies determine execution order (MVP-18 precedes MVP-16).

## Global constraints and execution method

- Preserve original planning documents and all unrelated dirty/untracked work. New-file paths below are proposed ownership locations, not claims they exist. MVP-01 freezes exact allowed files/interfaces/migrations and task-specific command lines before handoff; a directory in a row never authorizes editing all of it.
- No code, dependencies, tests, provider calls, repository creation, migrations or remote writes until explicitly authorized for the bounded task. No commits/pushes are implicit. Product GitHub writes against a designated test repository also need explicit authorization.
- ND01 and ND13 are approved. Other unresolved policies block dependent work; do not copy proposed prices, retention, retry or settlement constants as binding values.
- Existing demo execution/tests cannot be used as real isolation or product acceptance. Inspect test side effects before authorizing any suite. No customer code on trusted Laravel/FastAPI hosts, unsafe local fallback, simulated success, auto-merge or automatic overage.
- Every future implementation task follows: [ ] read frozen handoff/dependencies; [ ] write meaningful failing behavioral test; [ ] run and inspect correct RED; [ ] implement minimal change in allowed files; [ ] observe GREEN and refactor; [ ] run authorized relevant regression/security checks; [ ] submit sanitized evidence and diff; [ ] Codex review and acceptance. Missing harness/provider is BLOCKED, not RED. Documentation/review-only tasks use document/evidence validation rather than artificial TDD.
- Tests listed below are required future coverage, not executed evidence. Future child handoffs name exact installed commands, test selectors, fixtures, environment and expected failure; do not assume an uncreated test path is executable today. Typical PHP selector uses `php artisan test --filter=<approved class>` in backend; PostgreSQL checks use the reviewed `phpunit.postgresql.xml`; Python checks use the approved interpreter and unittest/pytest harness; browser/build commands depend on approved scripts. Never run setup scripts blindly.

## Shared interfaces

ExecutionScope = server-issued immutable workspace/project/repository/SHA + target grants + profile/policy versions + budget reference. JobObservation = scope/run/attempt/generation/sequence + observed stage/outcomes + evidence refs + tool/model/image provenance. Finding = ID/source/severity/location/assertion/evidence + observation versus AI-judgment label. FixPlan = immutable digest/base SHA/allowed paths/enumerated jobs/verification/budget/Fix-credit count. Approval = plan digest + exact jobs/scope + current authorized approver/expiry. See architecture for wire routes; freeze typed DTO fields in MVP-01 before dependent implementations.

Every task consumes accepted outputs of listed dependencies and its approved slice contract. Its produced interface is the behavior named under specification/acceptance, using the shared identities above. Interface changes invalidate affected downstream contracts and require review; a queue payload cannot grant authority.

## Review focus

| Risk | Owning tasks and required evidence |
|---|---|
| Permission/consent revoked between enqueue and effect | 04/06/11/12/23/25: deny stale authority at actual boundary |
| Lost acknowledgment after reservation or GitHub success | 06/09/12/21/25/29: one durable reconciled logical effect |
| Hostile repo/page/model expands scope or leaks secrets | 10/14/15/16/17/18/24/30: enforced isolation and denied expansion |
| Missing runner/model/check becomes successful coverage | 12/13/18/20/26/31: explicit blocked/partial state, no fabricated PASS |
| Old artifact/notification survives membership or lifecycle change | 08/20/27/28: current access denial and private retention proof |

## Proposed sequential execution order

MVP-01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 11 → 12 → 13 → 14 → 15 → 17 → 18 → 16 → 19 → 20 → 21 → 22 → 23 → 24 → 25 → 26 → 27 → 28 → 29 → 30 → 31 → 32. This is a valid topological order, not permission to auto-advance. Each task retains its individual approval gate.

## Task definitions

### MVP-01 — Approve the MVP execution contract

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M0. **Dependencies:** none.
- **Proposed files / responsibility:** `docs/MVP/{MVP_PLAN,MVP_TASKS,MVP_ARCHITECTURE,AGENT_WORKFLOW}.md`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-001–042; SG-01–12.
- **Implementation specification / produced contract:** Approve private-evaluation scope/deviations and task-specific permissions; freeze DTO/error/state conventions, runtime versions, provider/model/tool/privacy decisions, real-evaluation mode, target proof, retention, quota settlement, budgets and retry values for the next slice. Record unresolved values as blockers.
- **Acceptance criteria:** Owner-approved task contract names allowed files/commands/environment and exact acceptance oracles; no R1 completion or commercial authorization inferred.
- **Required tests / evidence:** Document review covers every included feature, ND dependency and SG area; conflict scenarios reject development-only evidence as real-provider proof.
- **Security considerations:** No code/test/install/provider action until explicit permission; no guessed numerical policy.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-02 — Establish a reproducible baseline and reuse evidence

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M0. **Dependencies:** 01.
- **Proposed files / responsibility:** `backend/tests/{Feature,Integration,Unit}/; ai-agent/test_agent.py; frontend/package.json; docs/MVP/MVP_PROGRESS.md`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-039/040.
- **Implementation specification / produced contract:** Inventory tracked/untracked foundations and choose an owner-approved isolated baseline preserving user work. Record lockfiles/tool versions and exact narrow existing checks. Execute only separately authorized checks; classify failures and reuse candidates.
- **Acceptance criteria:** Every reused module has revision/diff provenance and actual targeted results or explicit NOT EXECUTED/BLOCKED; demo tests are distinguished from acceptance.
- **Required tests / evidence:** After authorization: targeted identity/artifact/credit PHP tests; PostgreSQL concurrency suite; Python unit checks; frontend build. Commands and dependency readiness verified before execution.
- **Security considerations:** Current ScanLifecycleTest may invoke host demo execution: inspect/quarantine it before test authorization; never run broad suite blindly.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-03 — Harden OAuth sessions and current-user bootstrap

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M1. **Dependencies:** 02.
- **Proposed files / responsibility:** `backend/app/Http/Controllers/Auth/GitHubOAuthController.php; backend/routes/web.php; backend/tests/Feature/Identity/GitHubOAuthTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-009/010; SG-01.
- **Implementation specification / produced contract:** Retain Socialite state validation and stable GitHub identity. Add authenticated session/workspace summary for the UI; remove normal UI reliance on /api/dev/session. Review personal-workspace bootstrap so login cannot silently restore revoked/changed ownership.
- **Acceptance criteria:** Real OAuth success/cancel/state-error/logout states work; session rotates; forged session and demo auto-login cannot authenticate evaluation users.
- **Required tests / evidence:** OAuth integration fixtures for invalid/replayed state, duplicate identity, logout/CSRF, ownership transfer then relogin, production/evaluation demo route denial.
- **Security considerations:** No plaintext OAuth/App tokens in responses/logs; provider smoke uses approved account only.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-04 — Enforce workspace and membership boundaries

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M1. **Dependencies:** 03.
- **Proposed files / responsibility:** `backend/app/Domain/Identity/; backend/app/Http/Controllers/Identity/; backend/tests/Feature/Identity/; backend/tests/Integration/Acceptance/IdentityMembershipLifecycleTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-010; ND01; SG-02.
- **Implementation specification / produced contract:** Reuse exact 17-action four-role matrix, current membership decisions, invitations/transfers and audit. Apply shared resource-parent checks and revocation semantics to new consumers. Expose basic team management APIs without new role grants.
- **Acceptance criteria:** Owner/Admin/Member/Viewer grants exactly match authoritative PRD; last-owner and invited-identity protections persist; stale sessions/jobs cannot retain revoked authority.
- **Required tests / evidence:** Full role/action table, two-tenant resource IDs, invitation reuse, last-owner concurrency, transfer races, inactive membership and audit failure behavior.
- **Security considerations:** Workspace Owner is never a platform operator; no client role/workspace claim grants authority.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-05 — Verify GitHub App installation and repository authority

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M1. **Dependencies:** 04.
- **Proposed files / responsibility:** `backend/app/Domain/GitHub/{RepositoryBindingService,GitHubAppClient}.php; backend/app/Http/Controllers/GitHub/; backend/tests/Feature/GitHub/RepositoryConnectionTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-009/022/033; SG-03.
- **Implementation specification / produced contract:** Implement server-correlated App installation completion and short-lived installation token brokerage. Fetch provider-selected repositories and bind immutable IDs to a permitted workspace; reject client-invented repo metadata/installation claims.
- **Acceptance criteria:** OAuth login alone grants no repository access; connect/disconnect requires matrix permission and current provider visibility; connection neither clones nor debits usage.
- **Required tests / evidence:** Fake provider contract plus separately approved real App smoke; installation substitution, selected-repo changes, revocation, rename/transfer and token expiry.
- **Security considerations:** Minimal App permissions, encrypted keys, no fork-token exposure; provider approval prerequisite for real calls.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-06 — Persist signed webhooks and revocation events

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M1. **Dependencies:** 05.
- **Proposed files / responsibility:** `backend/app/Domain/GitHub/GitHubWebhookVerifier.php; backend/app/Http/Controllers/GitHub/GitHubWebhookController.php; backend/app/Domain/GitHub/ProviderInbox.php; backend/tests/Feature/GitHub/WebhookInboxTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-003/020; SG-03.
- **Implementation specification / produced contract:** Validate raw-body signature/size/event schema; atomically store delivery ID and body fingerprint. Process install suspension/deletion/repository removal before future effects; durable replay-safe dispatch only for opted-in configured branches.
- **Acceptance criteria:** Duplicate delivery causes no duplicate logical effect; conflicting same-ID body is rejected/audited; revoked access blocks queued checkout and writes.
- **Required tests / evidence:** Invalid signatures, replay/parallel delivery, reordered events, missing fields, reconnect/rate limit, worker crash after inbox commit.
- **Security considerations:** Only webhook route gets justified browser-CSRF exemption; webhook payload cannot supply trusted workspace authority.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-07 — Create project and target authorization APIs

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M1. **Dependencies:** 05,06.
- **Proposed files / responsibility:** `backend/app/Domain/Projects/; backend/app/Models/{Project,TargetAuthorization}.php; backend/routes/web.php; backend/tests/Feature/Projects/ProjectAuthorizationTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-001/006/007/029.
- **Implementation specification / produced contract:** Define one project per selected repository binding with versioned Web settings and separately proven target origins/environment/actions. Read-only metadata defaults can be confirmed/overridden through validated settings.
- **Acceptance criteria:** Connect/list/configure projects works without scan or credit cost; no live target is executable until separate authority and action scope validate; unsupported profiles are explicit.
- **Required tests / evidence:** Two tenants, Member settings denial, expired/revoked target proof, deceptive origins, production mutation request, no-target source-only scan.
- **Security considerations:** Proof method and renewal are approved policy; owning a repository does not authorize an arbitrary website.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-08 — Bind private evidence to scan and tenant

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M2. **Dependencies:** 04.
- **Proposed files / responsibility:** `backend/app/Domain/Artifacts/; backend/app/Http/Controllers/Scans/ScanController.php; backend/app/Models/Artifact.php; backend/tests/Feature/Artifacts/ScanArtifactIsolationTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-012/018/022; SG-06/10.
- **Implementation specification / produced contract:** Reuse artifact service with explicit scan/attempt ownership and validated content metadata. Replace shared latest-failure.png delivery with immutable artifact refs. Resolve parent workspace→scan→result→artifact at every access; add safe collection validation and revocation-aware retrieval.
- **Acceptance criteria:** Tenant A cannot read tenant B result by mixing route parents; screenshots differ by run; expired/revoked links deny; no public raw file fallback.
- **Required tests / evidence:** Cross-parent ID permutations, stale link/current revocation, corrupt MIME/hash, traversal/symlink, oversized images/logs, HTML injection, storage rollback.
- **Security considerations:** Approve retention/crypto/link rules first; redact text and suppress unsafe images rather than claiming string masking sanitizes screenshots.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-09 — Implement bounded evaluation usage accounting

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M2. **Dependencies:** 04,01.
- **Proposed files / responsibility:** `backend/app/Domain/Credits/; backend/config/devguard-{limits,mode,credit-test-policy}.php; backend/tests/Integration/Acceptance/CreditReservationConcurrencyTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-015–017/036/038; SG-09.
- **Implementation specification / produced contract:** Keep existing Scan/Fix accounts and atomic reservation. Add explicitly approved noncommercial evaluation allowance/period/settlement policy for real jobs; maintain a separate fail-closed real-evaluation operating contract. Leave synthetic settlement test-only.
- **Acceptance criteria:** One logical full Scan reserves one Scan credit; Fix jobs separately accounted; retry never grants/debits twice; exhausted jobs never provision runner; no payment/overage.
- **Required tests / evidence:** PostgreSQL concurrent last-credit race, idempotency body conflict, reserve/outbox atomicity, cancellation/platform error, period boundary, forged entitlement and real-evidence mode denial.
- **Security considerations:** No paid entitlements or guessed refund/reset values; existing local-only mode must reject unauthorized real runs.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-10 — Validate external isolation adapter and network boundary

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M2. **Dependencies:** 07,08,09.
- **Proposed files / responsibility:** `backend/app/Domain/Runners/; backend/tests/Unit/Domain/Runners/; backend/tests/Integration/Runners/IsolationBoundaryTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-022–026/037; SG-04/05/06.
- **Implementation specification / produced contract:** Implement one approved external provider behind RunnerProviderContract with provision/execute/collect/contain/cleanup semantics and immutable leases. Separate trusted checkout, untrusted commands and browser credential phases; enforce network/resource limits outside customer control.
- **Acceptance criteria:** Actual controlled adversarial workload cannot reach trusted hosts, metadata, other tenants or credentials; all lifecycle exits destroy/quarantine resources; unavailable runner is BLOCKED.
- **Required tests / evidence:** Real approved isolation tests: IPv4/IPv6/private ranges, redirect/rebinding, DNS/protocol bypass, filesystem/process escape, resource exhaustion, timeout/cancel/crash cleanup; contract fakes alone insufficient.
- **Security considerations:** Provider security/privacy approval is a hard prerequisite; no local proc_open fallback or secret-bearing command environment.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-11 — Enforce four emergency controls from first dispatch

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M2. **Dependencies:** 10.
- **Proposed files / responsibility:** `backend/app/Domain/Operations/; backend/app/Domain/Runners/; backend/tests/Integration/Operations/SelectiveContainmentTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** ND13; PD-039; SG-12.
- **Implementation specification / produced contract:** Create authenticated platform-operator controls for new scans, Fix, GitHub writes and runners independently; implement live checks/fencing at admission/dispatch/effects and safe containment/recovery contracts.
- **Acceptance criteria:** Affected new effects stop; safe evidence delivery/cleanup/reconciliation continue; enabling one control never resumes another or replays stale approval.
- **Required tests / evidence:** Control unavailable, concurrent disable/dispatch, active job containment, unauthorized workspace Owner operation, restart fencing and controlled resume.
- **Security considerations:** Use approved KS-01–10 exactly; no automatic refund/cancellation assumption or broad workspace operator grant.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-12 — Build durable scan admission and lifecycle

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M2. **Dependencies:** 06,07,08,09,10,11.
- **Proposed files / responsibility:** `backend/app/Domain/Scans/ScanExecutionService.php; backend/app/Http/Controllers/Scans/ScanController.php; backend/app/Jobs/RunScan.php; backend/tests/Feature/Scans/ScanAdmissionTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-003/016/019/036.
- **Implementation specification / produced contract:** Replace synchronous demo execution with transactionally persisted scope/run/reservation/outbox and 202 response. Pin SHA, policies/capabilities/targets; use attempt generation, heartbeat, bounded events and cancellation. Recheck live authority before dispatch.
- **Acceptance criteria:** Actual stage events drive polling/reconnect; duplicated requests reuse logical run; missing output/provider never fabricates results; disconnected repo/denied actor never dispatches.
- **Required tests / evidence:** Queue crash/replay, duplicate/out-of-order observations, stale worker callback, revoke after enqueue, cancel/provision race, terminal-state conflict and no demo fallback.
- **Security considerations:** Remove hardcoded installation/repo creation from normal scan path; whitelist commands/profiles in isolation only.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-13 — Execute real repository discovery and baseline checks

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 12.
- **Proposed files / responsibility:** `backend/app/Domain/Discovery/; backend/app/Domain/Scans/; backend/tests/Integration/Scans/WebBaselineTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-004/005/007/022/029/041.
- **Implementation specification / produced contract:** Read bounded manifests without evaluation; detect React/Vite and vanilla JS separately. Pin checkout SHA and execute declared approved build/lint/tests under profile budgets. Normalize command output and explicit missing/unsupported checks.
- **Acceptance criteria:** Two supported real fixtures produce attributable results; no-check/no-script is excluded coverage rather than PASS; non-Web profiles are detected/planned only.
- **Required tests / evidence:** Clean/defective fixtures, malicious package scripts, nested paths/monorepo selection, huge files/symlinks, lock mismatch, command failure, unsupported version and stale summary rejection.
- **Security considerations:** Install dependencies only within approved isolated phase/network policy; no repo-provided configuration can disable boundary checks.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-14 — Execute authorized non-destructive live Playwright checks

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 12,13.
- **Proposed files / responsibility:** `ai-agent/runtime/web/; backend/app/Domain/Targets/; backend/tests/Integration/Scans/LiveTargetPolicyTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-001/005/006/026/029.
- **Implementation specification / produced contract:** Run pinned trusted Chromium recipes in separate external browser isolation: load/render, console/network errors, navigation and reviewed assertions. Enforce proof/origin/action scope across every request/frame/redirect and honest deployment identity.
- **Acceptance criteria:** Authorized source-only and source+live scans work; unknown deployed SHA remains unknown; production mutations and unauthorized navigation fail closed.
- **Required tests / evidence:** Real controlled target with redirects/rebinding/subframes/WebSockets, destructive controls, timeout, unavailable target, mismatched deployed SHA and browser crash cleanup.
- **Security considerations:** URL prevalidation alone is insufficient; no arbitrary AI-generated Playwright code on trusted hosts or production actions based solely on HTTP verb.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-15 — Deliver brokered test-account browser login

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 08,10,14.
- **Proposed files / responsibility:** `backend/app/Domain/Artifacts/CredentialBroker.php; ai-agent/runtime/web/login.py; backend/tests/Integration/Scans/TrustedLoginTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-027/028; SG-06.
- **Implementation specification / produced contract:** Implement one guided approved login flow with redacted selectors and isolated trusted browser context. Broker secrets only to approved consumer and target; store references, not values; approve state-changing test fixture authority.
- **Acceptance criteria:** Authenticated checks operate only in permitted environment; secret cannot reach AI/repo scripts/evidence; expired lease fails; MFA/CAPTCHA yields handoff.
- **Required tests / evidence:** Broker expiry/revocation/replay, hostile DOM/selector exfiltration, logout/session cleanup, credential masking, accidental screenshot capture and production write denial.
- **Security considerations:** Do not share secret-bearing browser profile with arbitrary repository browser scripts.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-16 — Add bounded AI-selected browser test plans

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 14,15,18.
- **Proposed files / responsibility:** `ai-agent/planning/web_plan.py; ai-agent/runtime/web/; ai-agent/tests/test_web_plan_policy.py`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-005/028/029; QTD-01/02.
- **Implementation specification / produced contract:** Map sanitized risk analysis to versioned trusted recipes and allowed assertions, targets and budgets. Validate plan schema/capability/policy before browser execution. Record instruction/tool version/digest and coverage exclusions.
- **Acceptance criteria:** Risk-targeted browser checks really execute through approved recipes; injection/unknown action cannot expand scope; when no approved recipe applies, report an explicit coverage gap; never fabricate executed coverage.
- **Required tests / evidence:** Hostile page/repo instructions, unsupported recipe/version, cap exceedance, nondeterministic assertion, plan timeout and observable defect fixture.
- **Security considerations:** No automatic runtime skill publication, arbitrary eval or customer instruction privilege; registry simplification requires MVP-01 approval.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-17 — Run basic security scanners with private findings

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 13.
- **Proposed files / responsibility:** `ai-agent/scanning/; backend/app/Domain/Findings/; ai-agent/tests/test_security_normalization.py`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-012/039; SG-06/08.
- **Implementation specification / produced contract:** Select and pin approved secret/dependency/SAST tools and rules for JS profiles. Run in isolation with bounded inputs; normalize severity/rule/file/evidence/advisory database version, limitations and suppression decisions.
- **Acceptance criteria:** Seeded credential/dependency/code issues are detected with masked evidence; clean fixtures assessed; scanner unavailable/old advisory data is explicit partial coverage.
- **Required tests / evidence:** Seeded fixtures, false-positive/negative review, malicious config/output, path traversal, scanner timeout, unavailable advisory feed and redaction regression.
- **Security considerations:** Never validate discovered secrets by contacting providers; no real leaked credentials in fixtures; scanner feature is not SG certification.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-18 — Harden private FastAPI and model contract

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 08,12.
- **Proposed files / responsibility:** `ai-agent/{main,schemas,gemini_analyzer,config}.py; ai-agent/tests/test_internal_service.py`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-002/037/038; SG-06/08.
- **Implementation specification / produced contract:** Replace public unauthenticated/wildcard-CORS assumptions with private authenticated workload API. Bound request bodies/snippets/model calls; validate structured response and model provenance. Retain heuristic diagnostics only as explicitly heuristic, never a substitute for actual model review.
- **Acceptance criteria:** Unauthorized caller denied; malformed/oversized/model-error responses cannot become verified findings or writes; timeout produces partial analysis; no provider secret reaches client/log.
- **Required tests / evidence:** Authentication/replay/scope mismatch, request limits, invalid JSON, provider timeout/rate limit, redaction, model-config absence and prompt injection.
- **Security considerations:** Approved provider/data use required before sending private code; no new model/provider commitment or paid call without scope authorization.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-19 — Produce evidence-grounded AI code review

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 13,17,18.
- **Proposed files / responsibility:** `ai-agent/review/; backend/app/Domain/Findings/; ai-agent/tests/test_review_evidence.py`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-005/012/021/038.
- **Implementation specification / produced contract:** Review bounded source/diff and observed failures; require file/line/digest and evidence refs, separate observation from AI judgment, validate proposed lines and sanitize outputs. Consolidate duplicates without hiding source scanner findings.
- **Acceptance criteria:** Actual authorized model review identifies reviewed seeded defects with valid source references; uncertainty/model unavailability is displayed; no fixed ShopFlow diagnosis or unsupported confidence claim.
- **Required tests / evidence:** Development-only clean/defective fixtures (not the final holdout), invented line/evidence, malicious repository prompt, contradictory scanner/model output, huge diff budget and flaky failure.
- **Security considerations:** LLM score is not empirical confidence; AI cannot grant tool/network/PR privileges.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-20 — Assemble private reports and comparable history

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M3. **Dependencies:** 08,13,14,16,17,19.
- **Proposed files / responsibility:** `backend/app/Domain/Reports/; backend/app/Http/Controllers/Scans/ScanController.php; backend/tests/Feature/Reports/ReportCoverageTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-012/018/019/029.
- **Implementation specification / produced contract:** Assemble severity/findings, actual assertion results, source/live identity, provenance, exclusions, cleanup and model state. Provide paginated scan history, compatible-run comparison and sanitized JSON/HTML export with private artifacts.
- **Acceptance criteria:** Each finding links to its own evidence; failed tests differ from infrastructure failure; partial scans never read fully passed; incompatible comparisons explain limits; export is authorized.
- **Required tests / evidence:** Mixed coverage, zero executed checks, artifact expired, foreign comparison ID, stored HTML injection, source/target mismatch and pagination stability.
- **Security considerations:** Never persist whole checkout or inline secrets; CSP/escaping and private cache controls apply to report delivery.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-21 — Publish GitHub Checks and grounded review comments

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M4. **Dependencies:** 06,19,20.
- **Proposed files / responsibility:** `backend/app/Domain/GitHub/CheckPublisher.php; backend/app/Domain/GitHub/ReviewPublisher.php; backend/tests/Integration/GitHub/ReviewEffectsTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-003/020/021; SG-03.
- **Implementation specification / produced contract:** Opt-in push/PR events create deduplicated scans through admission. Publish actual Check progress/conclusion, summary and nonblocking inline comments on valid current diff lines; use durable effect intents and trusted publisher.
- **Acceptance criteria:** Same logical event produces one scan/credit/effect; comments cite evidence, omit secrets and cannot modify source; stale/missing diff line becomes safe summary/handoff.
- **Required tests / evidence:** Fork PR, branch filter, duplicate delivery, GitHub timeout-after-success, permission revoke, changed head, rate limit and write-switch disable.
- **Security considerations:** Never expose write token to fork checkout; publication authority is independent from model recommendation.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-22 — Create grouped immutable fix plans

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M4. **Dependencies:** 19,20.
- **Proposed files / responsibility:** `backend/app/Domain/Fixes/FixPlanService.php; backend/app/Models/{FixPlan,FixJob}.php; backend/tests/Feature/Fixes/FixPlanTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-008/030.
- **Implementation specification / produced contract:** Group findings by root cause into explicit jobs with affected paths/base SHA, verification scope, risk, resource budget and independent Fix credit count. Propose permission creates plan only; plan edits produce new digest/version.
- **Acceptance criteria:** Member may propose but cannot approve; Owner/Admin sees exact proposed jobs/files/cost scope; no code changes or PR intent executes during planning.
- **Required tests / evidence:** Cross-tenant findings, duplicate grouping, modified plan digest, excessive paths/jobs, unsupported Fix profile and quota display.
- **Security considerations:** Reject secret/binary/workflow-sensitive changes outside explicit policy; immutable scope prevents broad approve-all-future behavior.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-23 — Approve exact fix jobs with current authority

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M4. **Dependencies:** 09,11,22.
- **Proposed files / responsibility:** `backend/app/Domain/Fixes/ApprovalService.php; backend/app/Http/Controllers/Fixes/; backend/tests/Feature/Fixes/ScopedApprovalTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-008/010/030; SG-07.
- **Implementation specification / produced contract:** Bind approval to workspace/repository/base SHA/plan digest/file scope/enumerated jobs/budget/expiry and current Owner/Admin. Reserve Fix usage and outbox atomically; revalidate before any patch effect.
- **Acceptance criteria:** Viewer/Member cannot approve; changed head/plan/budget/revoked actor denies; replay creates no extra job or reservation; approval explicitly covers PR intent.
- **Required tests / evidence:** Parallel approve, stale browser/revoked membership, SHA change, job substitution, scope expansion, expiry and control disable.
- **Security considerations:** Approval is permission for named effects only, never arbitrary model tool use or remote push to DevGuard repository.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-24 — Apply and verify patches in fresh isolation

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M4. **Dependencies:** 10,13,16,18,23.
- **Proposed files / responsibility:** `ai-agent/fixes/; backend/app/Domain/Fixes/FixExecutionService.php; backend/tests/Integration/Fixes/PatchVerificationTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-002/017/022/031.
- **Implementation specification / produced contract:** Generate bounded patch proposal; validate paths/diff against approved scope; apply on pinned base in fresh isolated workload. Execute original failure check plus approved regression, lint/build/security checks on exact patched digest. Bound attempts/time/tokens and reconcile logical Fix settlement.
- **Acceptance criteria:** Verified result identifies exact revision and actual checks; out-of-scope patch never applies; unsuccessful/budget-exhausted fix yields private handoff and no automatic PR; retries do not add Fix credits.
- **Required tests / evidence:** Symlink/path escapes, hidden workflow changes/secrets, malicious patch, reproduced failure, regression, provider loss/cancel/retry exhaustion and stale approval before apply.
- **Security considerations:** No patch code on trusted service; no verification bypass by AI or auto-published unverified draft.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-25 — Create approved verified Draft PR and reconcile effects

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M4. **Dependencies:** 05,11,21,24.
- **Proposed files / responsibility:** `backend/app/Domain/GitHub/DraftPrPublisher.php; backend/tests/Integration/GitHub/DraftPrPublicationTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-002/008/031; SG-03/07.
- **Implementation specification / produced contract:** Trusted publisher checks current approval/installation/head/write control and verified digest, creates scoped branch/commit then Draft PR. Persist intent before external action; reconcile ambiguous remote results before retry. Track PR state in report.
- **Acceptance criteria:** Approved verified fix creates exactly one Draft PR with sanitized verification summary; head change requests reapproval; no auto-merge or unauthorized branch overwrite.
- **Required tests / evidence:** Real approved test-repository smoke, timeout-after-branch/commit/PR success, concurrent publisher, revoked App, changed head, provider rate limit and kill switch between effects.
- **Security considerations:** MVP DevGuard development session never pushes automatically; product test-repo writes require explicit separate authorization.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-26 — Replace demo UI with real product workflow

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M5. **Dependencies:** 07,12,20,22,23,25.
- **Proposed files / responsibility:** `frontend/src/{App.jsx,components/,pages/,api/}; frontend/src/index.css; frontend/tests/`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-007/012/019/029/030.
- **Implementation specification / produced contract:** Use authenticated session/project APIs; repository selection/targets, actual scan stage polling, paginated history/report/evidence, usage, fix plan/approval and PR link. Split existing monolith by coherent view responsibility; remove timed fake stages and fixed demo labels.
- **Acceptance criteria:** User completes onboarding→scan→report→approve→Draft PR through real APIs; four roles see appropriate actions, backend still enforces; loading/empty/blocked/error states are accessible.
- **Required tests / evidence:** Playwright E2E in approved test environment for each role/two tenants, CSRF/session expiry, reconnect/out-of-order events, keyboard/focus, partial/model-error state and screenshot authorization.
- **Security considerations:** No browser localhost FastAPI dependency or hidden demo login; permission hiding alone is not authorization.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-27 — Deliver basic permission-aware notifications

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M5. **Dependencies:** 04,20,25,26.
- **Proposed files / responsibility:** `backend/app/Domain/Notifications/; frontend/src/pages/Notifications.jsx; backend/tests/Feature/Notifications/DeliveryPolicyTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-011/034.
- **Implementation specification / produced contract:** Create Dashboard notifications for report/fix/PR outcomes; important/critical Email with safe links, current recipient access/preferences and durable dedup/retry. Do not send routine success spam.
- **Acceptance criteria:** Actual event yields one eligible notification; revoked recipient never receives private content; notification delivery failure does not change scan status or credits.
- **Required tests / evidence:** Duplicate event, delivery timeout/retry, preference change, role revoke before send, expired report link and email content redaction.
- **Security considerations:** Email provider approval required; no Telegram, raw screenshots, source patches or secrets in messages.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-28 — Automate privacy lifecycle and cleanup reconciliation

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M5. **Dependencies:** 08,10,12,15,24.
- **Proposed files / responsibility:** `backend/app/Domain/Artifacts/ArtifactLifecycleService.php; backend/app/Domain/Operations/CleanupReconciler.php; backend/tests/Integration/Operations/LifecycleRecoveryTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-018/022/027; SG-04/06/10.
- **Implementation specification / produced contract:** Enforce approved artifact expiry/deletion, credential revocation, checkout disposal and abandoned lease reconciliation across normal/crash/cancel exits. Persist observed cleanup evidence and separately retain protected financial/audit records.
- **Acceptance criteria:** Expired artifacts inaccessible then removed; crashed runs leave no reusable secret/browser/checkout residue; uncertain cleanup is quarantined/audited and visible.
- **Required tests / evidence:** Worker kill, provider unavailable during cleanup, storage delete fail, duplicate sweeper, backup retention implications, lease expiry and cross-tenant cleanup isolation.
- **Security considerations:** Do not claim secure deletion beyond provider evidence; no automatic deletion of unrelated user files.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-29 — Measure usage and validate recovery operations

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M5. **Dependencies:** 09,11,12,25,27,28.
- **Proposed files / responsibility:** `backend/app/Domain/Operations/; backend/app/Domain/Usage/; backend/tests/Integration/Operations/RestoreDrillTest.php`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-037–039; SG-09/11/12.
- **Implementation specification / produced contract:** Add safe trace/metrics for stages, runner/model cost/tokens, queue/cleanup failures and remote effect reconciliation; alerts/runbooks. Execute approved backup/restore/migration rollback and post-restore fencing/consent checks. Measure P50/P95/p99 and worst-case budget behavior on approved workloads.
- **Acceptance criteria:** Restoration meets owner-approved RPO/RTO and does not revive grants/approvals/replay PRs; measured ceilings and emergency drills support evaluation scope.
- **Required tests / evidence:** Actual isolated restore, queue redelivery after restore, secret rotation, provider outage, duplicate effects, concurrent quota exhaustion and bounded load tests.
- **Security considerations:** No raw code/secrets in observability; cost data does not authorize prices/providers; production drills need separate permission.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-30 — Review adversarial security across all enabled boundaries

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M6. **Dependencies:** 26,27,28,29.
- **Proposed files / responsibility:** `backend/tests/Integration/Security/; ai-agent/tests/security/; frontend/tests/security/; docs/MVP/MVP_PROGRESS.md`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** SG-01–12; PD-039.
- **Implementation specification / produced contract:** Codex reviews full integrated diff and threat model; execute separately authorized P0 denial/abuse cases and approved independent security review where required. Map every SG area to real sanitized evidence and remediation/retest ownership.
- **Acceptance criteria:** No unresolved exploitable critical/high finding in agreed scope; authorization/injection/SSRF/escape/secret/approval/financial/recovery tests have actual evidence; unavailable checks block release.
- **Required tests / evidence:** Full two-tenant four-role matrix and revoke races; signed webhook/body conflict; real isolation network/cleanup; hostile model/page/repo data; stale approval/PR and ledger crash reconciliation.
- **Security considerations:** Do not run exploit probes against third parties or production; self-review alone is not independent external assurance.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-31 — Validate end-to-end quality with independent fixtures

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M6. **Dependencies:** 16,17,19,24,25,26,30.
- **Proposed files / responsibility:** `backend/tests/Integration/Acceptance/MvpJourneyTest.php; ai-agent/tests/benchmarks/; frontend/tests/e2e/; docs/MVP/MVP_PROGRESS.md`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-005/012/038/039; QTD-03/04.
- **Implementation specification / produced contract:** Use versioned clean/defect/holdout Web fixtures and reviewed labels independent of generation. Keep final holdout labels and results unavailable to implementation/model-prompt tuning and patch-generation workflows; a separately controlled evaluator records final results. Evaluate detection, false positives/negatives, evidence correctness, flaky/nondeterministic behavior and patch regression on actual pipeline; approve measured thresholds before scoring.
- **Acceptance criteria:** Both Web profiles complete source/live and approved fix journeys; withheld defects and clean cases satisfy approved quality thresholds or release is blocked; no fixture-only constant outputs.
- **Required tests / evidence:** Real GitHub→scan→private report and approval→verified Draft PR; actual model/provider tests plus deterministic policy tests; replay/outage/partial runs and compatible history comparison.
- **Security considerations:** Benchmark author/labels and evaluation must be independent of patch-generating outputs; record tool/model/image versions and exclusions.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

### MVP-32 — Approve controlled pilot and release disposition

- **Status:** NOT STARTED / NOT AUTHORIZED. **Milestone:** M6. **Dependencies:** 01,30,31.
- **Proposed files / responsibility:** `docs/MVP/{MVP_PROGRESS,MVP_PLAN}.md`. Existing files are reviewed before reuse; new paths are created only after approval.
- **Requirements:** PD-039/040; SG-01–12.
- **Implementation specification / produced contract:** Review all task evidence/deferrals/provider privacy/budgets and authorize a narrow pilot with explicit accounts/repositories/targets/data retention and rollback/incident owner. After pilot record results and explicit Go/No-Go; keep commercial/public launch separate.
- **Acceptance criteria:** Owner signs evaluation release scope with actual evidence, no unsupported R1/public/commercial claim; missing policy/security/integration proof yields No-Go.
- **Required tests / evidence:** Release checklist maps 12 requested feature areas, all SG gates and benchmark results; approved pilot actual observations plus tested shutdown/rollback and documented residual risks.
- **Security considerations:** No deployment/account registration/payment/push automatically follows acceptance; each consequential operation retains its approval boundary.
- **Definition of Done:** task-specific acceptance and evidence above, plus the shared DoD below; Codex accepts the actual diff and observed results. No successor starts on agent self-report.

## Shared Definition of Done and case format

Each accepted task has approved scope, satisfied dependencies, reviewed code/diff, no unrelated changes, no secrets, meaningful authorized tests with actual command/exit/output and environment/revision provenance, limitations and cleanup evidence, and Codex acceptance in MVP_PROGRESS.md. A failed/blocked check is never waived by completing the checkbox. Milestones add integrated regression and security review; full suites are not rerun after every small change without reason.

Acceptance cases are recorded with ID `MVP-xx-AC-nn`, PD/SG references, platform=Web, release=independent MVP, priority (P0 for security/authorization/quota), actor/permission, fixtures/environment, Given/When/Then, expected state/side effects, level/automation target, and status `SPECIFIED / NOT EXECUTED`. The task-level criteria above are the catalog; MVP-01 freezes detailed cases for the next slice, without editing the original 293 cases. Example MVP-08-AC-01: Given a signed-in Viewer of A and a result belonging to B, when A's workspace is combined with B's scan/result IDs, then evidence access is denied and no screenshot bytes leave the service (P0 integration, PD-010/012, SG-02/10, SPECIFIED / NOT EXECUTED).

M0 approves documentation/contracts only. Implementation begins with MVP-02 evidence work under explicit test permission; the first code correction should be the MVP-08 cross-parent evidence boundary if baseline review confirms it, within a separately approved scope and respecting dependencies. MVP-03/04 remain the normal identity foundation sequence. No implementation task is currently READY or ACCEPTED.
