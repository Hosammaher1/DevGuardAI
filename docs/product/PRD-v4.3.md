# DevGuard AI — Product Requirements Document (PRD) v4.3

**Status:** Consolidated planning baseline; NOT an implemented system, NOT a measured/priced launch specification.  
**Decisions:** PD-001–PD-042 (approved product choices); ND01 Fixed Role Matrix and ND13 Selective Containment explicitly APPROVED by the product owner. Other ND decisions remain unresolved.
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
| PD-010 | Personal and basic team workspaces, Owner/Admin/Member/Viewer roles and strict multi-tenant authorization; exact individual permission matrix is approved under ND01 (§3.1.1). |
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
- **ND01 is APPROVED:** enforce the fixed role matrix and mandatory policies in §3.1.1. Prior role-grant proposals are superseded.

### 3.1.1 ND01 — APPROVED: Fixed Role Matrix

**Authority:** Explicit product-owner approval in this documentation task supersedes the former unresolved ND01 status. Exactly four workspace roles exist: **Owner**, **Admin**, **Member**, **Viewer**. The following **RBAC-MATRIX** grants are approved policy; implementation/schema/security validation remains future work.

| Action | Owner | Admin | Member | Viewer |
|---|---|---|---|---|
| View authorized projects and reports | Yes | Yes | Yes | Yes |
| Connect authorized repositories | Yes | Yes | No | No |
| Start scans | Yes | Yes | Yes | No |
| View authorized scan results | Yes | Yes | Yes | Yes |
| Propose AI Fix plans | Yes | Yes | Yes | No |
| Approve AI Fix execution | Yes | Yes | No | No |
| Approve unverified Draft PR creation for review | Yes | Yes | No | No |
| Cancel own jobs, subject to safety policy | Yes | Yes | Yes | No |
| Manage project settings | Yes | Yes | No | No |
| Invite members | Yes | Yes | No | No |
| Remove Members or Viewers | Yes | Yes | No | No |
| Change Member/Viewer roles | Yes | Yes | No | No |
| Grant or revoke Admin role | Yes | No | No | No |
| Manage billing and subscriptions | Yes | No | No | No |
| Manage sensitive workspace settings | Yes | No | No | No |
| Transfer workspace ownership | Yes | No | No | No |
| Delete workspace | Yes | No | No | No |

All grants require active membership and authority over the resource; Yes is never a bypass of GitHub consent, environment safety, quota, approval or sandbox policy. Unspecified actions are denied, including attempts to apply a Member/Viewer management grant to an Admin or Owner.

| Clause | Mandatory authorization policy |
|---|---|
| RBAC-01 | Default deny for unspecified actions. |
| RBAC-02 | All authorization decisions must be enforced server-side. |
| RBAC-03 | Every action must be scoped to an active workspace membership and authorized resource. |
| RBAC-04 | A user cannot promote themselves or grant permissions beyond their own authority. |
| RBAC-05 | An Admin cannot appoint another Admin or Owner. |
| RBAC-06 | Membership invitations require acceptance by the intended identity. |
| RBAC-07 | Expired, revoked, or already-used invitations must be rejected. |
| RBAC-08 | Ownership transfer requires authorized confirmation and acceptance by the new owner. |
| RBAC-09 | Never remove the last active Owner without a safe, completed ownership transfer. |
| RBAC-10 | Membership removal or role reduction revokes future authorization immediately. |
| RBAC-11 | Revalidate authorization before sensitive execution boundaries, including sandbox dispatch, AI Fix execution, GitHub writes and billing actions. |
| RBAC-12 | Fix approvals must be bound to their precise workspace, repository, commit, file scope, fix plan and execution budget. |
| RBAC-13 | Maintain auditable records of role changes, approvals and denied privileged actions without exposing secrets. |
| RBAC-14 | Workspace roles never grant platform-level emergency Kill Switch authority. |
| RBAC-15 | GitHub permissions, consent, sandbox policies and security gates remain independently enforced. |

**Technical details still open:** API/schema and transaction/locking choices, identity-bound invitation tokens and expiry duration, ownership confirmation/acceptance mechanism, distributed revocation consistency, audit storage and sensitive-setting classification must be specified and validated under T01/T02. These details do not reopen the approved grants. No additional role or unspecified action is implicitly granted; ND03 financial settlement and all other pending ND policies remain separate.

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

### 3.5.1 ND13 — APPROVED: Selective Containment (option C)

**Authority:** Explicit product-owner approval supersedes the former unresolved ND13 status. This platform incident policy is distinct from workspace RBAC and ordinary job cancellation.

| Clause | Mandatory emergency policy |
|---|---|
| KS-01 | Four independent platform-level controls: Scan, AI Fix, GitHub Write and Sandbox/Runner. A disabled dependency (for example a runner) can block dependent work without changing another switch's state. |
| KS-02 | Activation and resumption require currently authorized platform operators. Workspace Owner/Admin/Member/Viewer roles confer no platform emergency authority. |
| KS-03 | Prevent new affected side effects immediately at the trusted action boundary, including queued work, concurrent dispatch and retries. Already-submitted provider effects are handled by KS-06; activation is not a claim to undo a remote effect. |
| KS-04 | Cancel, quarantine or isolate unsafe in-flight work according to incident scope; preserve truthful interrupted/partial/blocked state. The exact containment mechanism is a technical design choice, not permission to drain unsafe work. |
| KS-05 | Continue safe trusted cleanup, auditing and reconciliation. Repository-defined cleanup hooks and arbitrary customer commands do not become trusted or exempt from isolation, authority or emergency policy. |
| KS-06 | Reconcile already-submitted GitHub operations using observed provider state, including lost acknowledgments. Do not assume cancellation, duplicate a write, or bypass a disabled GitHub Write control with a reconciliation retry. |
| KS-07 | Fail closed at dangerous execution boundaries when trusted switch state is unavailable. Only independently authorized safe trusted cleanup/audit/reconciliation may continue; unknown state never permits unsafe dispatch or resumption. |
| KS-08 | Reconcile credit reservations idempotently under separate ND03 financial policy, using durable logical-job identities. ND13 approves no refund, cancellation charge, reset time or extra debit. |
| KS-09 | Resume only after authorized incident review, verified remediation and current-policy revalidation of membership, resource authority, GitHub consent, precise Fix approval, quota, budget, capability and applicable switch state. Re-enabling one control does not re-enable another or replay unsafe work. |
| KS-10 | Record activation, attempted unauthorized control, incident/scope, impacted jobs, containment, remote-effect resolution, credit reconciliation, recovery/resumption and operator identity with protected audit provenance; expose no secrets or cross-tenant data. |

T01 refines technical contracts against this approved policy. T06 enforces Sandbox/Runner controls, T08 Scan admission/dispatch, T09 GitHub Check/summary/comment writes, T14 AI Fix admission and T15 patch/retry/Draft PR publication. Every boundary applies all relevant independent controls. T18 owns integrated containment/resumption verification; controls are not deferred until T18. Mechanisms for operator provisioning, policy distribution/fencing, containment, read-only provider reconciliation and audit durability require technical validation. No vendor, timeout or consistency implementation is selected here.

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

### 3.8 Expert Runtime QA Skill System

**Design scope:** Documentation design requested by the product owner, refining PD-005/007/012/013/026–029/038–042 without changing PD-001–PD-042, approved ND01/ND13 or the remaining ND decisions. No runtime skill, Codex Skill, framework, runner or benchmark has been installed, executed, validated or deployed by this document.

#### 3.8.1 Capability catalog and release boundaries

| Capability ID | Runtime specialty | First usable milestone / scope | Required behavior |
|---|---|---|---|
| RQA-STRATEGY | QA Test Strategy and Risk-Based Testing | R1 Web and shared planning; extend per vetted profile R2–R5 | Risk/coverage plan tied to actual repository/target evidence; no fabricated coverage. |
| RQA-DESIGN | Test Case Design | R1 Web/shared; extend R2–R5 | Equivalence partitions, boundaries, decision tables, state transitions and relevant negative/concurrency/cleanup cases. |
| RQA-WEB | Web Automation | R1 React/Vite and vanilla JS; extra Web profiles separately vetted | Real deterministic checks and feasible isolated Playwright; brokered login and production safety still apply. |
| RQA-API | API and Integration Testing | R2 Backend/API then Full-Stack | Contract/auth/role/service/database fixtures on vetted isolated profiles; R1 development API tests are not this customer capability. |
| RQA-SECURITY | Security and Authorization Testing | R1 authorized Web scope; repeat relevant boundaries R2–R5 | Tenant/role/approval/SSRF/injection/secret checks in authorized fixtures; no unlimited production attack authority. |
| RQA-PERFORMANCE | Performance and Concurrency Testing | R1 authorized isolated Web fixtures; extend R2–R5 | Bounded load/race/resource observations; no production load or capacity/SLA claim without separate authorization and measurement. |
| RQA-ACCESSIBILITY | Accessibility Testing | R1 declared Web diagnostic checks; later profiles independently declared | Evidence-backed supported checks and manual limitations; no invented WCAG certification, blanket coverage or new ND09 release gate. |
| RQA-DATA | Test Data and Fixtures Management | R1 isolated Web data; R2 services/DB; R3 corpora; R4/R5 device/OS fixtures | Authorized synthetic/scoped fixtures, secret brokerage, tenant isolation and cleanup; no uncontrolled production mutations. |
| RQA-MOBILE | Mobile Testing | R4 Android, then separately vetted iOS | Actual compatible emulator/device/macOS/Xcode profiles and private session cleanup; discovery alone enables nothing. |
| RQA-DESKTOP | Desktop Testing | R5 per vetted Electron/Tauri/native OS profile | Matching isolated GUI/build/process/filesystem/network runner and per-OS evidence. |
| RQA-AI-EVAL | AI Application Evaluation | R3 agent/chatbot/RAG | Versioned deterministic/rubric/tool/grounding/injection/regression observations; separate from R1 AI-assisted scan planning. |
| RQA-TRIAGE | Defect Triage and Root-Cause Analysis | R1 actual Web findings; extend R2–R5 | Evidence-grounded grouping and hypotheses with confidence/limitations; no source modification privilege. |
| RQA-RELEASE | Release Quality Gates | R1 available scope; repeat R2–R5 | Check required real evidence and deny unsupported readiness claims; no deployment, merge or emergency authority. |

R1 shared contracts describe later capabilities as PLANNED; only R1 Web and shared capability implementations, fixtures and quality evidence are R1 prerequisites. R2 API/Full-Stack, R3 customer-AI evaluation, R4 Android then iOS, and R5 Desktop extend the same contracts and repeat relevant security/quality gates before availability. Future native providers, versions or ND08 datasets cannot block R1. Accessibility diagnostic capability does not resolve ND09 or invent a formal compliance promise.

#### 3.8.2 Normative runtime design clauses

| Requirement | Observable design requirement |
|---|---|
| QA-001 | Runtime QA Skills are versioned planning/analysis instructions and validated capability metadata consumed by the Python AI Orchestrator. Codex development Skills guide building DevGuard and are a separate system; neither kind alone executes tests or proves quality. Real testing frameworks and authorized isolated runners produce observations. |
| QA-002 | Trusted Capability Registry and Scan Planner select skills by platform/module, task, release, exact supported version, independent Scan/Fix capability and current authorization. Record selection reasons and explicit exclusions. PLANNED/BLOCKED/UNSUPPORTED profiles cannot be enabled by an LLM or repository text. Shared R1 selection must not require later API/AI/native providers. |
| QA-003 | A manifest identifies skill ID/version, content digest, provenance/review state, orchestrator/manifest schema compatibility, adapter/tool/profile dependencies, authorized tool requirements, input/output and evidence contracts, dataset/benchmark versions, execution budget requirements and permitted release scope. Validate it as data against the trusted distribution/review policy before admission; invalid or incompatible manifests grant no authority. Serialization, version scheme and integrity mechanism remain QTD-01. |
| QA-004 | Skill versions are immutable after review; updates require compatibility validation and regression evidence before promotion. Pin the selected version and digest for a run. Rollback selects a previously validated compatible version under current policy, does not silently change a running attempt, restore revoked permissions, widen tools or claim new versions verified. |
| QA-005 | Effective tools are the intersection of the reviewed manifest, trusted actor/workspace/repository/target scope, adapter capability, consent, precise Fix approvals, provider policy and current emergency controls. Revalidate at each dangerous boundary; a skill or model cannot grant a permission. Untrusted checkout/build/test/browser/patch work stays in external managed isolated runners; trusted orchestration never executes customer code. Broker secrets to approved trusted login only. |
| QA-006 | Repository files, webpages, test outputs, retrieved documents and model/tool responses are untrusted task data. They cannot install/replace skills, edit trusted manifests, select an unauthorized tool, alter network phase, disclose secrets, expand scope/budget, disable evidence checks or operate Kill Switches. Trusted user input still requires server authorization; quoting an instruction or role name does not make it authoritative. |
| QA-007 | Select relevant test techniques from observed risk and declared capability: partitions/boundaries, decision/state models, negative/authorization, concurrency/replay, timeout/cleanup and partial coverage as appropriate. Record which supported risks are exercised and excluded; don't invent a coverage percentage, require mandatory staging/login for safe partial scans, or select unavailable techniques as executed. |
| QA-008 | Keep deterministic assertions and AI judgments distinct. Deterministic outcomes require observed assertions; AI diagnosis/rubric opinions retain evidence links, uncertainty and limitations and never silently upgrade a failed/unexecuted test to PASS. Confidence must have a stated assessed basis; no invented probability or universal accuracy promise. A hypothesis without execution evidence is labeled unverified. |
| QA-009 | Every finding/result binds to job, workspace scope, source SHA or explicitly unknown live revision, actual platform/profile, case/assertion, selected skill/digest, tool/model configuration, runner and dataset identities plus private sanitized evidence. Root-cause grouping must preserve individual evidence and cannot fabricate a defect from a model assertion alone. |
| QA-010 | Evaluation datasets/benchmarks are versioned, provenance-recorded, authorized, integrity-checked and protected from tenant leakage. Maintain labeled defect fixtures and clean controls, independent evaluation/holdout material and documented limitations; customer secrets/private code are not silently repurposed as shared evaluation data. Dataset tampering, contamination or missing labels invalidates the affected evaluation. R1 skill benchmarks are separate from ND08's R3 customer-AI evaluation corpus. |
| QA-011 | Measure false positives and false negatives separately against known clean and defect-bearing fixtures, stratified by declared capability/profile. Record sample identities, denominators, misses, unsupported scope and nondeterministic variation. Unexecuted/partial evaluation is not zero errors. Approve benchmark datasets, minimum sample coverage and quality/regression thresholds before production promotion (QTD-03); numeric values are not established here. |
| QA-012 | Apply approved ND04 CPU/RAM/disk/wall-time/token/model/browser/concurrency and retry ceilings across all selected skills within the authorized logical run. Timeouts, denied tools, runner loss and exhausted budgets yield truthful partial/failed/blocked coverage, safe cleanup and private retained observations without unsafe fallback. Internal skills do not create extra Scan credits; Fix retries/verification follow PD-017 and financial settlement remains ND03. |
| QA-013 | Audit skill selection/admission/rejection, versions, scope/policy, authorized tools, budgets, observations, nondeterministic model settings, rollback and exclusions with secret-safe provenance. Reproduction uses pinned compatible fixture/tool/runner/skill identities and records remaining nondeterminism; it does not promise identical model text. Exact record formats and private artifact links remain QTD-04 and ND05. |
| QA-014 | Before any runtime skill version is usable in production, require reviewed provenance/manifest and compatibility, authorized tool/runner boundaries, injection and privacy checks, deterministic-versus-judgment/evidence validation, independent behavioral evaluation on QTD-03 benchmarks including FP/FN/regression and budget/failure cases, and a validated rollback path. Missing, stale or mismatched evidence blocks that skill/profile. An independent qualified reviewer must assess behavior, not just manifest syntax. These gates supplement, never replace, applicable SG-01–SG-12 and platform release review. |

#### 3.8.3 Contracts, trust and promotion flow

Trusted approved scope + immutable source identity + observed discovery → Capability Registry/Scan Planner → eligible manifest/version and documented risk/technique plan → current policy/tool/budget/emergency admission → real isolated adapter/framework execution → deterministic observations and separately labeled AI analysis → sanitized scoped evidence/report → security and runtime-skill quality review.

Review/promotion and runtime execution are separate authorities. Runtime output cannot modify a development Skill, promote itself, change a registry entry, approve a fix, mutate source before approval or claim release readiness without actual evidence. Skill selection explains feasible partial coverage when optional credentials, profiles or techniques are absent; it does not make unsupported work pass.

| Technical record | Still to specify/validate | Required before |
|---|---|---|
| QTD-01 | Manifest serialization/schema, version/compatibility rules, digest/integrity and trusted publication/rollback mechanisms | T30 manifest admission and version handling |
| QTD-02 | Tool-broker/adapter interfaces, denied-action/error contracts, isolation integration and cancellation/checkpoint semantics | T32 execution boundary acceptance |
| QTD-03 | R1 per-capability benchmark datasets, clean/defect labels, holdout separation, FP/FN baselines, sample coverage and promotion/regression thresholds; extend separately per later release | T33 production eligibility; T34–T38 later-capability admission |
| QTD-04 | Provenance/evidence schema, confidence presentation/calibration and reproducibility records | T32/T33 evidence and quality acceptance |

These are explicit technical/QA validation records, not new approved financial values or replacements for ND02–ND12/ND14–ND15. QTD-03 R1 runtime-skill evaluation must not be confused with the R3-only ND08 product evaluation oracle. Unknown thresholds keep admission blocked; syntax validation is not behavioral evidence.

#### 3.8.4 Development Skills are separate artifacts

Use available Superpowers methods and the draft `devguard-acceptance-specs` for DevGuard development. Planned specialist development Skills remain unavailable unless separately authored. Do not create or install all custom Skills: propose a reusable development Skill only for a demonstrated guidance gap beyond the PRD, AGENTS.md and existing methods. Any future Codex Skill authoring must follow `superpowers:writing-skills`, including independent behavioral verification before reliance. Runtime QA manifests/instructions are product design artifacts, not claims that those Codex Skills exist or have passed validation.


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
| Exact role operation matrix | Fixed Owner/Admin/Member/Viewer grants and mandatory authorization policies in §3.1.1 | APPROVED ND01; implementation validation remains |
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
6. Do not invent test execution evidence. The original 106 tests/23 tasks were source-blocked drafts; the current catalog preserves their IDs and expands them against this available PRD. Specification, structural checks and approved policy never imply executed tests or release readiness.
7. This PRD does not approve app code generation, service scaffolding, deployment, git init, push, or purchasing cloud resources. User authorization is needed separately.

## 9. Acceptance review and release definition of done

- **For each spec:** purpose and source clause clear, observable assertions, unambiguous scope, negative path, clear data setup, precise PASS/FAIL oracle and review of unresolved values.
- **For each engineering task later:** red-first executable test, correctly observed failure, minimal implementation, targeted and full suites pass, security review and meaningful Git commit; no fictional results.
- **For each platform support release:** capability profile and compatibility declared, adapter contract tests, isolated-runner gates, truthful unsupported/partial labeling, monitored costs, recovery and cross-tenant denial tests.
- **For public launch:** PD-039 gate evidence (all SG-01..SG-12), critical/high exploitable flaws remediated, financially supportable subscriptions and quotas, privacy/legal/provider validation, limited pilot and explicit release sign-off.

**End — DevGuard AI PRD v4.3**
