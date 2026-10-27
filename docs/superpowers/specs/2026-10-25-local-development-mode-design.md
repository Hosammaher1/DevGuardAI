# DevGuard AI Local Development Mode Design

**Status:** Proposed for product-owner approval; documentation only. This document does not authorize application implementation, dependency installation, application-test execution, provider activation, payment work, deployment, or remote writes.

**Authority:** This temporary operating-mode design is subordinate to [PRD v4.3](../../product/PRD-v4.3.md), the [R1 Engineering Blueprint](devguard-r1-engineering-blueprint.md), the [Master Test Plan](../tests/devguard-master-test-plan.md), the [implementation plan](../plans/devguard-implementation-plan.md), the [decision register](../plans/devguard-pending-decisions.md), and [AGENTS.md](../../../AGENTS.md). Those sources retain the original product vision, commercial roadmap, approved decisions, acceptance catalog, and security gates.

**Current-state basis:** T01-T03 are committed on `dev`. The worktree also contains the preserved T04 local technical foundation and T25 provider-neutral runner contract preparation recorded in the progress ledger. T04 remains `PARTIAL`; T25 provider assurance remains incomplete; Master Test Plan cases remain `SPECIFIED / NOT EXECUTED`.

## 1. Intent

DevGuard AI needs a clearly isolated mode in which the core platform can be developed and validated locally before commercial payment and subscription functionality is implemented or activated. This mode is a temporary development overlay, not a reduced product, separate edition, launch configuration, or replacement roadmap.

Local Development Mode must:

- allow independently safe core work to proceed without payment-provider, checkout, real-charge, or subscription-enforcement dependencies;
- preserve the Credit Ledger, quota integrity, approved Scan/Fix separation, and all existing security guarantees;
- keep the complete commercial specification, proposed pricing, billing tests, and launch roadmap intact;
- distinguish local implementation evidence from provider, commercial, security-gate, and release evidence;
- fail closed whenever a core action still needs an unresolved security, isolation, authorization, provider, or policy prerequisite.

The mode is successful when the approved core local-validation milestone is demonstrated with fresh evidence. Success does not activate, authorize, or imply commercial readiness.

## 2. Goals, non-goals, and anti-goals

### 2.1 Goals

1. Define one explicit `local_development` operating mode with deterministic admission rules.
2. Keep real payment processing, checkout, payment-provider calls, real charges, and commercial subscription enforcement disabled throughout that mode.
3. Permit synthetic development entitlements and test-only ledger outcomes without treating them as production ND02/ND03 policy.
4. Permit local core development in dependency-safe slices while retaining all applicable RBAC, tenant, approval, evidence, secret, runner, and ND13 boundaries.
5. Provide truthful evidence labels and an auditable exit gate before any commercial work can start.

### 2.2 Non-goals

- Selecting or integrating a payment provider.
- Implementing checkout, paid subscription lifecycle, real financial webhooks, invoices, charges, refunds, proration, or commercial entitlement enforcement.
- Finalizing ND02 pricing/arithmetic, ND03 production settlement, ND04 production limits, ND11 trial eligibility, or ND15 commercial eligibility/geography.
- Weakening provider, sandbox, privacy, GitHub, AI, or Security-Gated Launch requirements.
- Reclassifying any Master Test Plan case as passed or executed.
- Removing, rewriting, or bypassing the existing Credit Ledger.

### 2.3 Anti-goals

The design fails if Local Development Mode can be mistaken for production, accepts real money, grants commercial entitlements, runs untrusted customer code on trusted Laravel/Python hosts, turns a mock into provider assurance, silently relaxes authorization or security policy, or permanently removes any commercial requirement.

## 3. Approaches considered

### 3.1 Selected: separate operating-mode overlay

Define Local Development Mode in this separate specification and a companion plan. Existing canonical documents receive only narrow references or sequencing annotations where necessary. This keeps temporary development mechanics distinct from the durable product and commercial baseline.

### 3.2 Rejected: rewrite the canonical implementation sequence

Moving or rewriting the commercial packages directly in the canonical plan would make a temporary restriction look like a product-scope decision and risks obscuring the original release graph. The existing plan also has preserved uncommitted T04-F sequencing work.

### 3.3 Rejected: create a local PRD edition

A second PRD would duplicate requirements and invite drift. PRD v4.3 remains canonical and unchanged in meaning.

## 4. Normative mode definition

### 4.1 Mode identity

The sole name is `local_development`. It is valid only when all of the following are true:

1. the application environment is explicitly `local` or `testing`;
2. the trusted operating-mode configuration explicitly selects `local_development`;
3. the local-mode policy version is recognized by the running code;
4. commercial guards are present and hard-disabled;
5. startup/admission validation finds no contradictory commercial or external-execution setting.

Environment alone is not sufficient. An omitted, unknown, malformed, or contradictory mode configuration fails closed. A production-like environment must reject `local_development`; it must not silently inherit local allowances.

The existing hard-false settings in `backend/config/devguard-billing.php` remain authoritative until a future, separately approved commercial implementation replaces them through an explicit design and security review. Local Development Mode must not add an environment-variable or request-level override capable of enabling them.

The authoritative mode record is the validated server configuration produced from `backend/config/devguard-mode.php`. Its canonical security-relevant fields are serialized in sorted-key form and identified by a SHA-256 policy fingerprint. Laravel HTTP processes, queue workers, and schedulers validate the mode name, version, environment, commercial guards, and fingerprint at boot. Any trusted command sent to the Python orchestrator carries the validated mode name, version, and fingerprint inside its authenticated command envelope; Python rejects a missing, unknown, or mismatched value before using tools or providers. The frontend receives read-only state from Laravel and has no independent mode authority.

LDM-01 owns the Laravel configuration/value object, HTTP/worker/scheduler boot validation, and read-only response contract. It does not add a Python or frontend runtime. Their consumers are implemented in the first later slice that introduces each process, before that process may perform a dangerous action.

### 4.2 Required operating banner and machine-readable state

Every locally served control-plane/UI surface must be able to obtain a trusted mode state containing at least:

- mode name: `local_development`;
- policy version;
- `commercial_payments_enabled=false`;
- `subscription_checkout_enabled=false`;
- `payment_provider_calls_enabled=false`;
- `real_financial_charges_enabled=false`;
- `commercial_subscription_enforcement_enabled=false`;
- local evidence classification;
- unavailable capability reasons.

Human-facing local surfaces must display a persistent **LOCAL DEVELOPMENT — NO REAL PAYMENTS** indicator. API and worker admission use trusted server-side policy, never the banner or client-supplied mode value.

### 4.3 Mode isolation

Local Development Mode is not selectable per user, workspace, request, route, or job. It is process/deployment scoped. All trusted processes participating in one local environment must agree on the same mode and policy version before dangerous work is admitted.

Mode state cannot be changed through the Dashboard, public API, repository content, webhook input, AI output, or untrusted runner. A mismatch between API, worker, Python orchestrator, or frontend-observed state blocks affected work and records a sanitized diagnostic.

Failure behavior is deterministic:

- a Laravel HTTP process, queue worker, or scheduler with a missing/unknown mode, unsupported policy version, invalid fingerprint, prohibited commercial setting, or invalid environment refuses to boot;
- a trusted process that detects a mode/fingerprint mismatch in already queued work marks that work `BLOCKED` before any dangerous effect, records sanitized evidence, and does not retry until current policy is re-established;
- a Python consumer rejects the command before model/tool/provider use;
- a frontend state mismatch makes affected controls unavailable and requests fresh trusted state, but frontend behavior never substitutes for server denial;
- LDM-01 does not add a recovery or diagnostics HTTP bypass. Operators use local CLI/config/test evidence until an authenticated diagnostics design is separately approved.

For this mode, configuration is contradictory when any commercial capability is true; a payment provider/driver, payment credential reference, financial-webhook handler, checkout route, or commercial subscription mutation is registered; local/test policy markers claim production approval; or untrusted external execution is enabled before the LM3 provider/isolation policy is present and approved. Approved non-payment GitHub, AI, email, storage, vault, queue, or isolated-runner configuration is not inherently contradictory, but each remains denied until its own task-specific policy and credentials pass. Trusted local PostgreSQL/queue/private storage is permitted. A mere unused environment variable that is neither loaded nor reachable is not mode state, but secret scanning still applies.

### 4.4 Permitted behavior

Subject to the normal task authorization and unresolved-decision gates, the mode may support:

- local Laravel, React, Python, PostgreSQL, queue, private-artifact, and approved supporting development services;
- synthetic users, workspaces, repositories, jobs, entitlements, ledger fixtures, and evidence explicitly marked local/test;
- current T01-T03 control-plane behavior and the preserved T04-F ledger foundation;
- provider-neutral contracts and inert local doubles that do not execute untrusted code or make external effects;
- deterministic build-independent contract, policy, UI, and orchestration tests when separately authorized;
- separately approved real non-payment integrations only under their existing authorization, security, provider, secret, and least-privilege requirements.

Local mode does not itself authorize GitHub, AI-model, email, vault, object-storage, sandbox, cloud, or other provider use. ND06/ND15 and task-specific prerequisites continue to govern each real integration.

### 4.5 Prohibited behavior

While `local_development` is active, the system must not:

- expose or execute checkout, payment-method capture, paid signup, invoice payment, charge, refund, payout, or payment-provider onboarding flows;
- call a payment provider or accept a real financial webhook;
- create, upgrade, downgrade, cancel, renew, or enforce a commercial subscription;
- translate synthetic entitlements or test ledger settlement into a financial claim;
- run untrusted repository checkout, build, tests, browser actions, patches, or cleanup hooks on trusted Laravel/Python hosts;
- treat a local mock, fixture, or provider-neutral contract test as sandbox, provider, security, commercial, or release acceptance evidence;
- bypass current membership, tenant, GitHub consent, scoped Fix approval, quota reservation, emergency control, secret, artifact, or capability checks;
- describe planned, blocked, partial, mocked, or unsupported behavior as available or passed.

Any prohibited path must be absent or deny before side effects. Hiding a UI control is not enforcement.

## 5. Commercial preservation and deferral

All payment, subscription, pricing, billing, credit, trial, quota, commercial viability, and public-launch requirements remain part of DevGuard AI. No PD decision, ND record, proposed numerical value, T16/T24/T22 work, DG-BILL/DG-COST/DG-GATE case, or SG-09 requirement is deleted, weakened, or marked rejected.

Until the post-validation gate in section 10 is approved, affected functionality is labeled:

> **PLANNED — DEFERRED UNTIL POST-VALIDATION**

This label applies to payment-provider selection/integration, checkout, real payment processing, paid subscription lifecycle, commercial subscription enforcement, commercial entitlement grants/resets, real financial webhooks, proration, charging/refunds, payout arrangements, and commercial activation. It does not apply to the Credit Ledger's secure local technical foundation, which remains preserved and usable only within its verified scope.

The temporary deferral must not be interpreted as approval of proposed prices or unresolved policy. Basic 10 and Pro 30 monthly Scan entitlements remain approved product values; proposed prices, Team cap, Fix allowances, margins, and other unresolved values retain their current status.

## 6. Credit Ledger and entitlement boundary

The existing Credit Ledger remains the single planned accounting boundary. Local Development Mode must preserve:

- separate workspace Scan and Fix accounts;
- exact one-credit reservation for a full Scan;
- atomic holds, tenant-bound relationships, actor-bound fingerprints, idempotency, concurrency controls, append-only ledger effects, and no negative available credit;
- current authorization requirements for Scan and Fix reservation;
- restrictive deletion and cross-tenant relationship protections.

Local development may seed clearly synthetic balances through test/development-only fixtures. Such seeding is not a trial grant, subscription entitlement, payment result, monthly reset, or production policy. It must be unavailable outside local/testing and must not rely on a commercial plan record.

`DevelopmentCreditSettlementService` and `devguard-credit-test-policy.php` remain test-only simulations. Their outcomes do not resolve ND03, close DG-CREDIT cases, or authorize a production settlement path. No new local shortcut may update ledger state outside the preserved transactional and append-only boundaries.

Commercial subscription enforcement is disabled, but security and quota integrity are not. Local core flows may use synthetic funded accounts; they may not treat “local mode” as unlimited credit or skip ledger admission where the eventual flow requires it.

## 7. External effects, runners, GitHub, and AI

### 7.1 Untrusted execution

Local operation does not weaken PD-022-PD-026 or SG-04/SG-05. If a validated isolated runner boundary is unavailable, untrusted execution stays blocked. Inert doubles may validate request formation, state transitions, cancellation, denial, and evidence plumbing, but they must not invoke arbitrary commands, checkout customer code, claim isolation, or close real-runner acceptance.

The existing T25 provider-neutral request/result contract is preserved. It is not a provider selection, runtime binding, credential path, or isolation guarantee.

### 7.2 GitHub

Local development may use synthetic GitHub fixtures and local webhook payloads. Real GitHub OAuth/App/webhook/write behavior requires the existing T05/T09/T15 security and authorization prerequisites. Any separately approved local real-GitHub setup must use development-only credentials, selected test repositories, verified signatures/consent, and no automatic merge. Local mode alone grants no GitHub write authority.

### 7.3 AI and Expert Runtime QA

The full AI testing, Fix, and Expert Runtime QA vision remains intact. Deterministic doubles may support local contract and UX work. Real model/provider calls require separate approval, sanitized least-privilege inputs, budgets, secret controls, and applicable ND06 assurance. Mocked AI output is labeled synthetic and cannot satisfy quality, security, or provider gates.

## 8. Evidence and status rules

Every result produced under this mode carries one of these evidence classes:

| Class | Meaning | May satisfy |
|---|---|---|
| `LOCAL_UNIT` | Deterministic unit behavior with no external effect | Local implementation checks only |
| `LOCAL_INTEGRATION` | Local trusted-component integration, possibly with local PostgreSQL/queue/storage | Local technical prerequisites only |
| `SYNTHETIC_CONTRACT` | Fixture/double-based boundary behavior | Contract preparation only |
| `ISOLATED_REAL_EXECUTION` | Real execution through a separately approved validated isolation boundary | Only the exact scoped technical evidence reviewed for that boundary |
| `PROVIDER_INTEGRATION` | Real separately approved non-payment provider behavior | Only provider-specific cases actually executed |
| `COMMERCIAL_ACCEPTANCE` | Future real commercial behavior after deferral exit | T16/SG-09/commercial cases only after authorization |

Local Development Mode initially produces only `LOCAL_UNIT`, `LOCAL_INTEGRATION`, and `SYNTHETIC_CONTRACT` evidence unless a real non-payment integration is separately approved. Evidence records include mode, policy version, source revision, configuration fingerprint, test command, timestamp, and synthetic/real-provider provenance.

The evidence classes apply to DevGuard job/report artifacts, provider/runner observations, formal validation runs, and evidence packets used to advance an LM milestone. Ordinary developer commands are not persisted as application-domain records; their command/output/count/provenance is recorded in the task progress checkpoint or approved validation packet. LDM-01 needs no new evidence database schema. Later job/report work stores the classification with the owning durable job/evidence record under the existing private-artifact and tenant rules.

No local result changes a Master Test Plan status automatically. Acceptance cases remain `SPECIFIED / NOT EXECUTED` until their canonical harness runs against the required real boundary and its evidence is reviewed. Local technical completion must always name its narrower scope.

## 9. Local-first sequencing overlay

This overlay does not renumber or delete T00-T38 and does not change the formal public-release dependency graph. It permits dependency-safe local child slices when their missing ancestor is purely a deferred commercial implementation or provider-specific effect not exercised by the child.

| Local milestone | Purpose | Required boundary | Explicit exclusions |
|---|---|---|---|
| LM0 — Mode boundary | Make local mode explicit, fail-closed, visible, and testable | T01-T03 current authority; existing billing guards | No payment/subscription/provider work; no app execution without separate authorization |
| LM1 — Trusted local core | Develop control-plane domains with local PostgreSQL/queue/private artifacts and synthetic entitlements | Current RBAC/tenancy/artifact/ledger guarantees | No untrusted execution, GitHub writes, model calls, or commercial claims |
| LM2 — Provider-neutral orchestration | Connect job/capability/planning/state/evidence contracts to inert doubles | T04-F only within verified scope; current T25 contract boundary | No claim of T04/T25 completion or actual isolation/provider acceptance |
| LM3 — Secure execution integration | Use a separately approved real isolated runner and any approved non-payment providers | ND06/ND15 scope, T06 isolation, secrets/network/cleanup controls | No trusted-host fallback; payments still deferred |
| LM4 — Core local validation | Demonstrate the approved core GitHub→Scan→Report and Approval→Fix→Draft-PR slices locally with actual required safe boundaries | Relevant T02-T15/T17-T23/T25/T30-T33 evidence as applicable; unresolved policies explicitly closed or excluded | No commercial acceptance, launch claim, or public availability |
| LM5 — Post-validation commercial work | Begin future T16/T24/commercial T22 work | Product-owner approval of LM4 evidence plus applicable ND02/03/06/11/15 decisions and security prerequisites | No automatic activation or production launch |

A child slice is independently safe only when it has no executable path to a deferred effect, its own required security/authorization boundary exists, and its evidence remains truthfully local. Missing runner isolation, secret handling, current authority, GitHub consent, precise Fix approval, or dangerous-effect control is a real blocker and cannot be waived by this overlay.

## 10. Exit and transition gates

### 10.1 Core local-validation gate

LM4 has the following minimum scope. A row marked real cannot be replaced by a mock in the LM4 qualification packet. “Synthetic” means the exact boundary stays visibly excluded from the qualification claim.

| Core boundary | Minimum LM4 behavior | Required evidence type |
|---|---|---|
| Identity, RBAC, tenancy | Current GitHub-authenticated or equivalently approved local identity, active workspace membership, ND01 authorization, immediate revocation, and cross-tenant denial | Real local control plane with PostgreSQL concurrency where relevant |
| Private artifacts and secrets | Private evidence authorization/revocation/expiry/redaction plus brokered job-bound credential handling for exercised flows | Real local storage/crypto/broker boundary; synthetic secrets only |
| Credit admission | T04-F exact-one Scan and separate Fix reservation with tenant/idempotency/concurrency guarantees | Real PostgreSQL ledger behavior using synthetic noncommercial balances; test-only settlement clearly labeled |
| Repository authorization | GitHub OAuth/App separation, selected development repository, verified webhook/consent/revocation, and scoped effects | Real GitHub development integration under separate approval; fixtures may supplement but not replace it |
| Runner isolation | Ephemeral untrusted checkout/execution, network/resource/lifecycle controls, collection, and cleanup | Real separately approved isolated runner; no trusted-host execution |
| Discovery and capability | React/Vite and vanilla-JavaScript detection, honest supported/blocked states, and provider-neutral adapter selection | Real repositories/fixtures through the trusted planner; no blanket support claim |
| Scan journey | Authorized selected-repository GitHub/manual input → reservation → isolated baseline/approved Web checks → truthful state/evidence/report | Real local control plane, GitHub development repository, PostgreSQL/queue/artifacts, and isolated runner |
| AI/Runtime QA journey | Risk/capability planning, bounded tools, provenance, evidence, and admission/rollback behavior for the R1 Web skills | Real separately approved model/tool boundary for the promoted AI claim; deterministic fixtures supplement negative cases |
| Fix journey | Grouped scoped plan → precise current approval → isolated patch/verification → verified Draft PR, plus failed/handoff path | Real local control plane, approved model if AI-generated, isolated runner, and selected GitHub development repository; no merge |
| Reports and Dashboard | Actual severity/status/coverage/history/evidence and persistent local-mode banner; blocked/partial/mocked states remain explicit | Real local UI/API over the exercised local data; email delivery may remain excluded |
| ND13 and recovery | Independent control denial at each exercised action boundary, queued/in-flight blocking, cleanup, audit, and safe reviewed resumption for the local topology | Real local control plane and exercised real external boundaries; no production RPO/RTO claim |
| Commercial boundary | No checkout, provider call, financial webhook, real charge, subscription mutation, or commercial entitlement enforcement | Negative configuration/route/network evidence only; all commercial cases remain unexecuted |

LM4 excludes payment/subscription implementation and acceptance, commercial notification/email delivery, cost/margin validation, pilot, deployment, public operations, and later-platform releases. Dashboard notification behavior may be included where needed for truthful local status; a real email provider is not required. Any other exclusion from the table requires a product-owner amendment before LM4 can qualify for the post-validation commercial-work gate.

The product owner may declare LM4 validated only after a reviewed evidence packet shows:

1. the agreed R1 core local journeys and negative cases run against the required real safe boundaries;
2. RBAC, tenant isolation, private evidence, secrets, quota/ledger integrity, approval binding, ND13 controls, and runner isolation evidence is current for the exercised scope;
3. no real payment, checkout, charge, financial webhook, or commercial subscription enforcement was used;
4. mocked or unavailable capabilities remain clearly identified;
5. failures, remaining decisions, and unexecuted Master Test Plan cases are recorded without promotion;
6. the working diff and evidence contain no secrets or unrelated changes.

LM4 validation is not R1 release readiness and does not satisfy T16, T24, T22, SG-09 commercial behavior, Security-Gated Launch, pilot, or Go/No-Go.

### 10.2 Commercial-work entry

Commercial implementation may begin only after a new explicit product-owner authorization following LM4 review. Before any real payment procedure or commercial subscription enforcement is implemented or activated, the applicable ND02/ND03/ND06/ND11/ND15 policy/provider/legal prerequisites must be resolved, a dedicated commercial design and threat review approved, and T16 child plans produced through the normal gated workflow.

There is no in-place toggle from Local Development Mode to commercial operation. A future commercial/production mode is a separately designed configuration and deployment boundary. Local data, synthetic entitlements, payment fixtures, and test credentials must not be promoted as production commercial state.

## 11. Failure behavior and security properties

- Unknown or inconsistent operating mode: fail startup or deny affected dangerous work.
- Missing local commercial guard: deny startup/admission; never default to enabled.
- Attempted payment/subscription path: deny before network, ledger, entitlement, or external side effect and emit sanitized audit/diagnostic evidence.
- Unavailable isolation or provider policy: report `BLOCKED`, never run on a trusted host.
- Mode mismatch during queued work: revalidate at action boundary and block; do not rely on cached admission.
- Synthetic/real provenance missing: evidence is invalid for promotion.
- Deferred requirement encountered by an otherwise independent slice: isolate the unavailable capability; do not silently implement or weaken it.

ND13 remains independently enforced. Local mode is not a fifth kill switch and does not confer platform-operator authority.

## 12. Documentation and traceability changes after approval

The companion planning task should make only these documentation changes:

1. add a short Local Development Mode reference to `AGENTS.md` without changing application authorization;
2. add an overlay reference and LM sequencing note to the implementation plan while preserving T00-T38, T04-F, and the commercial/release graph;
3. add a non-destructive Master Test Plan note stating that all commercial cases are preserved and local evidence does not execute them;
4. add an Engineering Blueprint reference clarifying local topology/evidence without altering the target architecture;
5. append a progress checkpoint recording the product-owner decision and documentation-only outcome;
6. create the companion dated implementation plan.

PRD v4.3 remains unchanged unless the product owner later requests a narrow cross-reference. Its product and commercial requirements already remain authoritative.

## 13. Concrete minimal next-step implementation brief

This brief is for use only after this design and its companion plan are approved and the product owner separately authorizes application implementation and application-test execution.

### LDM-01 — Explicit fail-closed mode policy

**Objective:** Add one trusted server-side `local_development` policy boundary that consolidates the existing hard-disabled commercial guards, exposes truthful read-only mode state, and prevents contradictory configuration without changing the Credit Ledger or adding any external integration.

**Smallest safe scope:**

- create `backend/config/devguard-mode.php` with the fixed mode name, policy version, allowed environments, evidence class defaults, and hard-false commercial capabilities;
- create `backend/app/Domain/OperatingMode/OperatingMode.php` and `LocalDevelopmentModePolicy.php` to validate configuration and return immutable trusted state;
- make `backend/config/devguard-billing.php` consume or mirror only hard-false policy values with no enabling environment override;
- add a read-only authenticated/local diagnostics response for mode state only if an existing diagnostics pattern supports it; otherwise defer the endpoint and prove the policy at service level;
- add `backend/tests/Feature/OperatingMode/LocalDevelopmentModePolicyTest.php` covering valid local/testing startup, production denial, unknown/missing mode denial, every commercial flag remaining false, contradictory configuration denial, immutable evidence classification, and absence of a request-level override;
- add a focused route/container assertion that no checkout, payment, charge, refund, billing-webhook, or commercial-subscription mutation route/service is registered;
- do not modify ledger migrations, models, reservation/settlement behavior, runner contracts, GitHub integrations, or provider bindings.

**Required RED evidence:** the focused test must initially fail because no unified operating-mode policy exists. It must not fail by calling a network service or real payment path.

**Required GREEN verification after authorization:** focused operating-mode tests, the relevant existing billing/credit configuration tests, full local SQLite regression, PostgreSQL integration/concurrency regression, Pint, PHP syntax for touched files, `composer validate --strict`, and a secret/diff review. Application-test commands remain prohibited until separately authorized.

**Completion evidence:** exact commands and counts; configuration snapshot proving all commercial capabilities false; route/service inventory showing no real commercial mutation path; diff limited to approved files; no secrets; no provider/network calls; no claim that commercial, provider, runner, or Master Test Plan acceptance passed.

**Prohibited in LDM-01:** payment SDK installation, checkout UI, provider credentials, financial webhooks, subscription entities/enforcement, synthetic-to-production promotion, changes to Credit Ledger guarantees, untrusted execution, external provider calls, deployment, or commercial activation.

## 14. Approval checkpoint

Approval of this design approves only the Local Development Mode definition and authorizes creation of its documentation plan. It does not authorize LDM-01 implementation, application tests, dependencies, provider use, real GitHub/AI effects, payments, deployment, or release activity.
