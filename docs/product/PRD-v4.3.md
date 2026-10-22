# DevGuard AI — Product Requirements Document (PRD) v4.3

**Status:** Consolidated planning baseline; NOT an implemented system, NOT a measured/priced launch specification.  
**Decisions:** PD-001–PD-042 (approved product choices).  
**Scope:** Public, multi-tenant AI quality assurance and assisted fixing SaaS; universal target architecture with phased platform releases.  
**Purpose:** Canonical requirements input for Codex acceptance test specifications and engineering plans.  
**No authorization to write application code or initiate deployment is conveyed by this document.**

## 1. Product mission and user experience

DevGuard AI reduces developer effort in finding, understanding and safely fixing defects. Users connect a GitHub repository, explicitly authorize the repository/scope, and receive useful tests and actionable reports with minimal setup (Zero-Configuration First). AI proposes fixes, but code modification and writing fix PRs require explicit, scoped user approval. Product vision covers Web, Backend/API, Full-Stack, AI Applications, Android/iOS mobile, and Windows/macOS/Linux desktop. Planned scope is not the same as generally available support.

### Representative user journeys

1. **Initial connection:** GitHub OAuth login → personal workspace → separate GitHub App installation for selected repositories → read-only smart discovery → authorized safe scan → evidence-based report.
2. **Automated scanning:** verified GitHub push/PR webhook → deduplicated queued scan if repository/branch authorized, feature enabled, quotas available and safe runner can execute → accurate GitHub Check and Dashboard report.
3. **Smart fixing:** grouped root-cause-oriented issues → fix plan specifies repository, commit, affected files, proposed scope, risks, budget and required Fix credits → explicit approval → isolated AI patch attempt → test/verify → verified Draft PR; no auto-merge.
4. **Failed fix:** bounded retry and cost → sanitized diagnostics and human review handoff → optional explicitly approved **unverified Draft PR**, visibly marked as unverified/human-review-only; never silently publish or call it verified.
5. **Subscription:** one free qualifying trial scan per real user before paid use; paid Basic, Pro and Team; shared workspace usage; fixed quotas with no automatic overages; suggested upgrade only, immediate after confirmed upgrade payment; downgrade/cancellation at next renewal.
6. **Cross-platform:** repository discovery detects available modules → capability registry reports what is really supported → approved adapters/runners perform platform-specific tests within constraints → unified results and platform-by-platform coverage. Unsupported and blocked cases are never described as passed.

## 2. Decision register — approved constraints

| Decision | Binding requirement |
|---|---|
| PD-001 | Hybrid GitHub source-repository and authorized live-website testing, as relevant. |
| PD-002 | Python AI agent may diagnose, propose and apply fixes only in an isolated authorized execution workflow, verify and open **Draft PR only**; never auto-merge. |
| PD-003 | Manual Dashboard scans and opt-in authorized automatic scans on selected GitHub push/PR events; verified webhook handling, deduplication and configured branch scope. |
| PD-004 | Initial concrete web stack support: React/Vite and vanilla JavaScript; additional stacks phased. |
| PD-005 | Hybrid deterministic existing build/lint/tests plus AI-planned risk-targeted Playwright E2E where technically feasible. |
| PD-006 | Non-destructive, in-scope testing for authorized production; state-changing actions require validated, isolated and authorized staging/test environment; SSRF controls. |
| PD-007 | Guided configuration from read-only repository metadata, allow confirmation/override only when necessary. |
| PD-008 | Explicit, informed approval **before any code modification or creation of fix PR**; approval bound to repository, commit, change scope and budget. Non-mutating automated review comments are an exception under PD-021. |
| PD-009 | GitHub login exclusively via Laravel Socialite OAuth; distinguish user authentication from GitHub App repository authorization. |
| PD-010 | Personal and basic team workspaces, Owner/Admin/Member/Viewer roles and strict multi-tenant authorization; exact individual permission matrix requires review. |
| PD-011 | In-app Dashboard and Email notifications only; no Telegram integration. |
| PD-012 | Advanced quality reports: severity, timelines/history, screenshots/logs/evidence, run comparison, AI diagnosis/fix and PR status. |
| PD-013 | Public multi-tenant SaaS; sandbox, quotas, abuse controls, isolation and release security gating mandatory. |
| PD-014 | Basic, Pro, Team monthly paid tiers plus a qualifying free trial scan; dollar prices unapproved. |
| PD-015 | One free scan per real user before payment (not three after paid subscription), no user BYOK, platform-funded AI, hard monthly entitlements, no pay-per-scan or auto-overage. **Basic=10 monthly scans, Pro=30 monthly scans approved; Team cap unapproved.** |
| PD-016 | One full authorized Scan Run consumes one Scan credit irrespective of internal test count, **within explicit resource/time/AI/browser bounds**; automatic/manual share workspace quota, atomic reservation/settlement, idempotency and refund/restoration for platform failure as policy dictates. |
| PD-017 | AI Fix has independent monthly quota; an authorized executed fix job counts even if unsuccessful except platform-error handling; bounded retries and verification do not consume new Fix credits. Numerical limits unapproved. |
| PD-018 | Uniform report/evidence retention duration across paid tiers; numerical days unapproved; billing/audit data separate retention. |
| PD-019 | Real stage-by-stage status: Queued, Provisioning, Running, Analyzing, Finalizing, Cleaning, Completed (plus appropriate Failed/Cancelled/Partial/Blocked), secure progress stream and reconnect; no invented percentages. |
| PD-020 | GitHub Checks, PR Summary and Inline Review Comments. |
| PD-021 | Automatic nonblocking, deduplicated, evidence-grounded Inline PR comments on valid diff lines; sanitize private data; these comments do not authorize source edits. |
| PD-022 | Private code ephemeral checkout in authorized isolated runner, teardown on success/failure/cancel/crash; never persist entire private repo as regular product data. |
| PD-023 | Managed isolated execution sandboxes for untrusted project scripts/code. |
| PD-024 | Provider-agnostic Sandbox Adapter interface, start with one security-vetted provider; no unsafe fallback. |
| PD-025 | Sandbox provider selection security/isolation gates first, price/performance second. Provider not yet chosen. |
| PD-026 | Controlled phase-specific internet allowlist, SSRF protection against private/local/link-local/metadata services including DNS rebinding/redirect tricks. |
| PD-027 | Secure test accounts for authenticated E2E tests; secrets from vault/broker, withheld from AI and untrusted repo commands; forbid CAPTCHA/MFA bypass. |
| PD-028 | AI-assisted guided login selector/flow setup, sanitized plan, human approval as needed, trusted Playwright verification. |
| PD-029 | Smart Auto-Discovery + Safe Defaults, Zero-Configuration First; useful authorized partial scans without mandatory staging/test login; accurately explain excluded coverage. |
| PD-030 | Smart grouped Fix Plan by root cause; include approval scope, affected files, commit, resource budget and Fix credit count; one approval can cover several **explicit** fix jobs; no writes until approved. |
| PD-031 | Bounded retry and Human Review Handoff on unsuccessful AI fix; do not silently publish unverified patches or double-bill retry. Retry numerical maximum unapproved. |
| PD-032 | Option to create **explicitly separately approved**, security-screened, plainly labeled **unverified Draft PR for human review only** after fix failure; never auto-merge, never call it verified. |
| PD-033 | Unlimited GitHub repository/project *connections* on all paid plans, subject to fair-use limits on concurrent execution, API rate, artifacts/storage; connecting repos never triggers cloning/execution by itself. |
| PD-034 | Smart-priority Dashboard/Email notifications: critical/important/info, dedup, recipient permissions/preferences; no routine success-email spam; no Telegram. |
| PD-035 | Upgrade immediately after verified successful payment and disclosed charges/proration; downgrade at next billing cycle; cancel stops renewal and preserves access through paid term; verified idempotent billing webhook handling. Mid-cycle upgraded quota arithmetic not finalized. |
| PD-036 | On quota exhaustion, deny additional scan/fix, disclose used/remaining/reset date and optional transparent upgrade. No surprise charge or auto-upgrade; automatic scans skip gracefully and accurately. |
| PD-037 | Hybrid Hosting: self-managed trusted Laravel API/workers and Python orchestration, managed PostgreSQL/queues as appropriate/private object storage/secrets, React static frontend/CDN; untrusted code solely in external managed isolated runners with no trusted-service access. No vendor chosen. |
| PD-038 | Cost-based pricing validation with per-scan/fix actual cost, AI tokens, retries, sandbox, browser, free trial, fixed overhead, P50/P95/p99 and stress/expected/base scenarios, budget gates and margin monitoring. Prices and unapproved caps NOT binding. |
| PD-039 | Security-Gated Launch: all mandatory security gates below passed with evidence, critical/high exploitable findings fixed, narrow authorized pilot and incident runbook before general release. |
| PD-040 | Production-Oriented Vertical Slices with security from first slice; GitHub→Scan→Report, then Approval→Fix→Draft PR, then multi-tenant commercial and release hardening. Fully featured product vision, no undersecured shortcut MVP. |
| PD-041 | Universal modular architecture and phased support of Web, Backend/API, Full-Stack, Android/iOS mobile, Desktop, and AI Applications. Adapter contracts, capability registry, platform-specific isolated runners, unified results and release gates; never mark planned support available. |
| PD-042 | Launch priority: **Web first**, **Backend/API next**, **Full-Stack**, **AI Applications**, **Android then iOS**, **Desktop**. Subsequent systems remain in ultimate product scope; capabilities phased and gate-tested. |

## 3. Detailed functional requirements

### 3.1 Identity, workspaces and tenant security

- OAuth flow verifies state/session, handles cancel, invalid/expired code, identity change, duplicate identities and sign-out; account identity must not be determined by mutable email alone.
- GitHub OAuth user login is distinct from GitHub App installation and installation permissions; users can only grant their own authorized repositories.
- Personal workspace and Team workspace membership; enforce Owner/Admin/Member/Viewer access for every API, queue job, report, signed artifact, fix approval, webhook processing and billing operation.
- Tenant scoping must be enforced server-side using trusted membership and policy, not submitted workspace IDs.
- **RBAC exact permissions beyond role names remain to be approved**; do not treat draft matrices as binding.

### 3.2 GitHub integrations and automated triggers

- Least-privilege GitHub App permissions and lifecycle; installation/uninstallation, permission changes, deleted/moved repos, branch rename, revoked access handled safely.
- Validate webhook signatures/timestamps/replay as appropriate; dedup repeated deliveries; retries, out-of-order events, concurrent push/PR, missing branch and stale SHA.
- Checks reflect actual Scan status: queued/running/completed/failed/skipped/blocked; no falsely successful checks when quota or permission prevents execution.
- Reports include diff-grounded inline comments and summary without duplicate comments or secrets.
- Fix publishing requires separate approval and operates only on an authorized pinned SHA/branch, with stale-SHA invalidation or explicit re-approval.

### 3.3 Project discovery and capability registry

- Read-only manifest/script/topology discovery with confidence and evidence; handle React/Vite, vanilla JS, Laravel and API runtime candidates, composite monorepos, Flutter/Android/iOS, Electron/Tauri, AI/agent configurations.
- If manifest ambiguous or incompatible, require only information necessary to proceed; do not promise nonexistent runners.
- Capability registry distinguishes PLANNED, SUPPORTED, PARTIAL, BLOCKED, UNSUPPORTED and PER-VERSION limits for scan and AI fix independently.

### 3.4 Scan lifecycle, quota, and reporting

- Every authorized Scan Run is scoped to repo, commit, website/test target, authorized environment, selected capabilities, time/resource/budget ceilings.
- Atomic quota ledger for manual/webhook scans; no duplicate charge for repeated delivery; platform-error refund/reconciliation, cancellation rules and monthly reset/timezone policies require final detailed specification.
- Scan execution stages and log streaming represent real events; reconnect/out-of-order events preserve correct monotonic job state.
- Build/lint/existing tests plus AI-planned Playwright on safe targets; report PASS/FAIL/PARTIAL/BLOCKED/UNSUPPORTED distinctly, attach sanitized evidence and actual coverage.
- Reports include severity, remediation suggestions, historical comparison and downloadable scoped/redacted artifacts.

### 3.5 Security and runners

- Untrusted checkout/build/tests/browser/patches execute only in managed isolated runners, not trusted app services; bound CPU, RAM, disk, wall time, token/model, concurrent jobs and egress.
- Network restrictions enforced beneath LLM prompts, protect against DNS rebinding, redirects, cloud metadata and internal addressing; egress allowlist tied to authorized target/phase.
- Test accounts mediated through trusted broker, no secrets included in LLM prompts, logs or arbitrary project scripts; MFA/CAPTCHA not bypassed.
- Runner crash, timeout, cancellation, cleanup/failure, contaminated image, filesystem persistence, cross-tenant overlap and replay are testable denial cases.
- Mobile/desktop OS-specific runner must meet same controls before usable by any user.

### 3.6 AI testing, Fix workflows and human review

- AI plans risk-based tests with deterministic checks and evidence; AI-generated tests cannot grant permissions or mutate production by default.
- Group findings by root cause, produce understandable fix plan and scoped, explicit approval; expose credit consumption and budget before commitment.
- Make patch only after approval in isolated environment; verify file scope/commit, sandbox budgets, build/tests/regressions, secret scans; verified Draft PR only when appropriate.
- Bounded internal retries included; when unsuccessful generate sanitized handoff package, no silently published invalid patch.
- Optional unverified Draft PR requires separate explicit authorization and clearly visible unverified status, human-only review.

### 3.7 Billing/notifications and information lifecycle

- Basic/Pro/Team monthly, one free qualifying scan pre-payment, independent workspace Scan and Fix quotas, no BYOK or auto-overages, optional upsell. GitHub unlimited connected repos subject to fair use.
- Financial events require validated signatures, idempotency keys, out-of-order event handling, reconciliation and atomic entitlement updates; failed/partial payment never grants unverified upgrades.
- Dashboard+Email prioritized notifications, opt-outs where appropriate, dedupe, least-privilege recipients.
- Report artifacts private and encrypted as appropriate, scoped access, expiring links, sanitization, scheduled retention/cleanup and separate audit/financial requirements; numeric report days pending.

## 4. Platform-specific acceptance scope and roadmap

| Release | Platform | Initial target | Special runner/adapter | Gate condition |
|---|---|---|---|---|
| R1 | Web | React/Vite and vanilla JS; extra Web frameworks later | Web Adapter + Linux sandbox + isolated Playwright browser | Secure full GitHub→Scan→Report and relevant Fix→Draft PR behavior; no promise of Next.js/Vue/Angular until verified |
| R2 | Backend/APIs | First vetted Laravel/Node/Python runtime profiles, API testing | Backend/API Adapter + isolated service/test DB runners | Safe authenticated APIs, scoped fixtures, contract/integration and role tests |
| R2 | Full-Stack | Composite Web+API with isolated test data | Composite Planner over Web/API adapters | Accurate cross-service diagnostics and capped one-Scan composite execution |
| R3 | AI Applications | Agent/chatbot/RAG evals incl. tool-call correctness, regression, hallucination/grounding, injection, cost | AI Evaluation Adapter + brokered/mock tools + evaluation runner | Deterministic and rubric-based eval evidence; no uncontrolled real side effects |
| R4 | Android | Vetted Android build/UI cases for supported versions and stacks | Android Adapter + managed isolated emulator | Clean device state, proper permissions, no cross-tenant residue |
| R4 | iOS | Vetted iOS build/XCTest/UI cases for supported versions | iOS Adapter + compliant managed macOS/Xcode simulator | Actual compatible provider and security/operational/licensing validation |
| R5 | Desktop | Electron/Tauri then compatible Windows/macOS/Linux native profiles | OS-specific desktop adapters and isolated GUI runners | OS-specific filesystem/network/process isolation and reliable cleanup |

- Platform additions cannot lower existing security controls or break adapter contracts.
- The Adapter contract must expose detect/plan/validate/execute/collect/cleanup and explicit capability metadata; execution is selected by trusted capability/policy engine rather than unbounded LLM choice.
- Successful test support does not imply Fix support; Fix requires appropriate verification runner and separate capability declaration.

## 5. Mandatory Security-Gated Launch criteria (PD-039)

**General availability must not proceed before evidence exists for ALL 12 areas**, scoped to available launch capabilities; each later platform release must re-run relevant gates:

| Gate | Required assurance |
|---|---|
| SG-01 Identity & Sessions | OAuth state/callback, session protection, token lifecycle, logout, account linking abuse resistance |
| SG-02 Multi-Tenancy & RBAC | No cross-tenant API/job/report/artifact/membership or financial data leakage; role escalation blocked |
| SG-03 GitHub Permissions | Least privilege, signed webhooks, installation lifecycle, correct scoped check/comment/PR writes, replay/idempotency |
| SG-04 Sandbox Isolation | Untrusted project code cannot reach trusted hosts/secrets/other tenants; verified lifecycle/cleanup and resource bounds |
| SG-05 SSRF / Controlled Network | Private/internal/metadata/redirect/DNS-rebinding blocked by enforced network controls |
| SG-06 Secrets Management | Brokered secret access, no exposure in AI prompts, reports, errors, runner logs or patches |
| SG-07 AI Change Approvals | Patch and PR writes require correctly bound approval; stale approvals rejected; no auto-merge |
| SG-08 Prompt Injection | Repo/test data/website content cannot override privileges, policy, scope, secrets or execution limits |
| SG-09 Billing / Quota Integrity | Atomic reservation, idempotent webhook, no double billing, no unauthorized entitlement upgrade or overage |
| SG-10 Artifact / Evidence Security | Tenant-scoped access, signed URL expiry, sanitization, private storage, retention cleanup |
| SG-11 Backup, Restore & Recovery | Recovery drills, documented RPO/RTO selection and verified restoration/migration rollback |
| SG-12 Monitoring & Incident Response | Observability, alerting, runbook detect→contain→assess→remediate→recover→review; emergency kill switches for scans/fixes/GitHub writes/runners |

Before public release: no unresolved exploitable critical/high findings from agreed threat-model test scope, acceptance evidence recorded, limited authorized pilot completed, and Go/No-Go decision documented. Security gate test outputs, not promises, determine readiness.

## 6. Delivery strategy and dependencies (PD-040/041/042)

Follow integrated vertical slices, security enforced from first working path:

1. Foundation: design docs, identity, workspace tenancy, policy/approvals data models, audit, queue, database, secret management contracts, safe sandbox abstraction, CI/spec checks.
2. GitHub OAuth/App + approved repo discovery → safe isolated baseline Build/Lint/Test scan → report with real state and quota semantics.
3. AI risk-based browser testing, evidence, report improvements, optional authorized live-site non-destructive testing and safe guided test-account configuration.
4. Grouped fix plan, user authorization, AI patch isolation, bounded retries/verification and verified Draft PR.
5. Human handoff/optional unverified review PR, PR comments/checks, idempotent GitHub writing.
6. Complete team/membership, billing/quotas, smart notifications and privacy lifecycle.
7. Observability, cost measurement, chaos/recovery, load/concurrency, financial validation and the required security gates.
8. Authorized pilot and release review; later platform adapters (R2–R5) expand by availability and gates, not by skipping the core.

**Dependencies:** Every test runner depends on trustworthy authorization and sandbox controls; AI Fix depends on findings, approved scoped plan, quota reserve and verification; Full-Stack depends on Web+API+fixtures; Android/iOS/Desktop depend on vetted matching OS runner; public launch depends on all applicable security gates and sustainable measured operations.

## 7. Numerical and technical values requiring validation — NOT approved

| Property | Current proposal or unresolved choice | Status |
|---|---|---|
| Basic plan price | $19/month | Proposal; must cost-validate |
| Pro plan price | $39/month | Proposal; must cost-validate |
| Team plan price | $99/month | Proposal; must cost-validate |
| Basic scan entitlement | **10/month** | **Approved** |
| Pro scan entitlement | **30/month** | **Approved** |
| Team scan entitlement | 100/month | Proposal |
| AI Fix allowances | Basic 5 / Pro 15 / Team 40 per month | Proposal |
| Artifact retention period | 30 days paid tiers | Proposal; **uniformity** approved, number not |
| AI retry count | Up to 3 retries per fix job | Proposal |
| Contribution margin goals | Target 60%; warning 40%; risk 20% | Suggested targets, unapproved |
| Example per-task costs | Illustrative only, not measured | Not a quote or vendor price |
| RPO/RTO | 1 hour / 4 hours | Suggested initial targets, unapproved |
| Upgrade prorated quota handling | Whether cap resets/credits at upgrade | Unresolved financial policy |
| Exact role operation matrix | Owner/Admin/Member/Viewer operation grants | Unresolved; explicit review required |
| Fair use/scan budgets | CPU, RAM, storage, time, token, concurrency, API rates | To measure and set |
| Provider choices | Sandbox, cloud, macOS, payment, email, models, vault, queue | Unselected; evaluate security, viability and cost |
| Live deployment sequencing | Detect exact finished deployment / SHA before post-deployment checks | Operational specification required |
| Compliance and billing geography | Payment provider eligibility, privacy policy, data regions, subprocessors | Verify prior to billing/public launch |
| Supported framework/version list | Specific Android/iOS/desktop/web versions and CI runner capabilities | To validate; no blanket claims |
| Free scan anti-abuse | Practical definition of real user / fraud prevention | Privacy-safe policy to finalize |
| Refund/cancel/reconciliation | Exact platform error and customer cancellation settlement policy | Define precisely prior to implementation |

## 8. Acceptance documentation rules for Codex

1. Use this file for product requirements; consult `AGENTS.md` and `devguard-acceptance-specs/SKILL.md` for process. If any conflict appears, flag it rather than inventing a resolution.
2. Do not treat proposed numbers as approved. Draft tests may parameterize unresolved constants and mark **NEEDS_DECISION** rather than hardcoding.
3. Every acceptance case requires: unique test ID; PD reference(s); release/platform; priority; actor/permissions; Given/When/Then; expected observable result; test type/automation target; security/quota side effect where relevant; status `SPECIFIED / NOT EXECUTED`.
4. Cover happy path, cancellation, failure, abuse, cross-tenant boundary, concurrency, replay, timeout, cleanup, partial capability, cost, rollback/recovery and safe GitHub writes.
5. Build an explicit 42-decision traceability matrix, listing at least one relevant test for each applicable decision and release gating for planned platforms; flag any uncovered requirement rather than claiming complete coverage.
6. Do not invent test execution evidence. The previously produced 106 tests and 23 tasks are **source-blocked drafts**; review and expand rather than blindly asserting sufficiency.
7. This PRD does not approve app code generation, service scaffolding, deployment, git init, push, or purchasing cloud resources. User authorization is needed separately.

## 9. Acceptance review and release definition of done

- **For each spec:** purpose and source clause clear, observable assertions, unambiguous scope, negative path, clear data setup, precise PASS/FAIL oracle and review of unresolved values.
- **For each engineering task later:** red-first executable test, correctly observed failure, minimal implementation, targeted and full suites pass, security review and meaningful Git commit; no fictional results.
- **For each platform support release:** capability profile and compatibility declared, adapter contract tests, isolated-runner gates, truthful unsupported/partial labeling, monitored costs, recovery and cross-tenant denial tests.
- **For public launch:** PD-039 gate evidence (all SG-01..SG-12), critical/high exploitable flaws remediated, financially supportable subscriptions and quotas, privacy/legal/provider validation, limited pilot and explicit release sign-off.

**End — DevGuard AI PRD v4.3**
