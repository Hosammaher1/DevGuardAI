# DevGuardAI independent MVP plan

Date: 2026-10-27. Status: PROPOSED — OWNER REVIEW REQUIRED. Planning only; no implementation authorization.

## Authority and separation

This is an independent delivery track. It does not replace, reorder, complete or amend the full-product roadmap, R1 tasks, decision register or acceptance catalog. All new planning lives in these six files. The authoritative requirements are [PRD v4.3](../product/PRD-v4.3.md), its PD-001–PD-042 and SG-01–SG-12, [decision register](../superpowers/plans/devguard-pending-decisions.md), [Engineering Blueprint](../superpowers/specs/devguard-r1-engineering-blueprint.md), [original plan](../superpowers/plans/devguard-implementation-plan.md) and [Master Test Plan](../superpowers/tests/devguard-master-test-plan.md). The root PRD is historical and differs from the authoritative product copy. Existing uncommitted edits remain user-owned.

The requested outcome is a real, end-to-end Web MVP for an explicitly authorized private evaluation/pilot, followed by a separate release decision. A private pilot is a proposed scope choice, not permission to handle customer code now. Public commercial availability remains deferred and cannot be claimed as full R1 completion. Owner approval must explicitly accept the narrower scope and operating-mode changes before implementation. Original security obligations remain binding for every enabled capability.

## Vision and intended journey

A developer signs in with GitHub, chooses a workspace, installs/connects the GitHub App, selects an authorized repository and optional separately authorized website, and starts a bounded scan. Real isolated build/lint/tests, browser checks, static security checks and AI review produce private findings and evidence. The dashboard shows actual stages, coverage and history. An authorized Owner/Admin reviews a grouped fix plan, approves precise jobs and budgets, and receives a verified Draft PR after isolated patch verification. Nobody auto-merges or silently publishes an unverified patch.

Success is measured through recorded end-to-end evidence on both clean and intentionally defective React/Vite and vanilla JavaScript fixtures, with two tenants, all four roles, provider failure, stale consent, quota races and malicious inputs. Counting tasks or screenshots alone does not establish acceptance.

## Approaches considered

1. Reuse the local demo as the MVP: quick visual progress, but fixed fixtures, simulated fallback, host-side execution and missing approval/publication boundaries make this unsuitable.
2. Build the full roadmap first: broadest coverage, but includes commercial and later-platform work beyond this independent MVP.
3. Recommended: retain reviewed identity/artifact/ledger foundations; replace demo execution with one vetted Web runner, a bounded AI service and durable Laravel workflow. Deliver broad product coverage with narrow supported profiles and a private evaluation boundary. This is the architect's recommendation awaiting approval, not an already selected owner decision.

## Included scope and traceability

| Feature | Practical MVP behavior | Tasks | Product anchors |
|---|---|---|---|
| GitHub login and teams | Socialite OAuth, personal/basic team workspaces, exact ND01 roles, current revocation | MVP-03–04 | PD-009/010 |
| Repository integration | App installation, provider-confirmed repository selection, scoped ephemeral tokens, disconnect/revoke | MVP-05–06 | PD-003/022/033 |
| Dashboard and projects | Real repository/project list, target configuration, job progress, actionable blocked/empty states | MVP-07/26 | PD-007/019/029 |
| Repository scanning | Read-only discovery, pinned SHA, actual baseline build/lint/tests in isolated execution | MVP-10/13 | PD-004/005/022–026 |
| Live Playwright tests | Separately authorized target, Chromium first, bounded non-destructive production checks; mutations only in verified test environments | MVP-14–16 | PD-001/006/027–029 |
| AI review | Bounded source/diff review and failure diagnosis with file/line/evidence references and explicit uncertainty | MVP-18–19 | PD-005/021/038 |
| Basic security scanning | Secrets detection with masked evidence, dependency advisories and bounded SAST; pinned approved tools/rules | MVP-17 | PD-012/039 |
| Evidence and reports | Private screenshots/logs, sanitizer, truthful coverage, severity, source/target attribution and report export | MVP-08/20 | PD-012/018/022 |
| Human-approved fixes | Grouped plan, exact SHA/files/jobs/budget approval, isolated patch and regression verification | MVP-22–24 | PD-002/008/030/031 |
| GitHub PR integration | Checks, summary, valid-line nonblocking review comments and approved verified Draft PR | MVP-21/25 | PD-020/021 |
| History | Paginated runs and comparison for compatible revisions/coverage; PR status links | MVP-20/26 | PD-012/019 |
| Notifications | Dashboard and important/critical Email, permission-aware delivery and deduplication | MVP-27 | PD-011/034 |
| Usage limits | Separate atomic Scan/Fix reservations, hard operational budgets, clear exhaustion and reset policy | MVP-09/12/24 | PD-015–017/036/038 |
| Operational safeguards | Four independent ND13 controls, audit, restore/cleanup drills, cost observations and release review | MVP-11/28–32 | PD-037–040 |

## Explicit deferrals and simplifications

- Web execution initially covers approved React/Vite and vanilla JavaScript profiles only. Laravel and FastAPI are DevGuard's implementation stack, not promises to test arbitrary Laravel/Python customer applications.
- API, full-stack, AI application evaluation, Android, iOS and desktop adapters remain PLANNED/UNSUPPORTED. Discovery can identify them without implying execution or Fix support.
- One vetted external isolation provider and one pinned Chromium image/profile first. No Kubernetes/multi-region fleet or custom workflow platform is required. Ordinary containers on trusted hosts are not a substitute for the approved isolation boundary.
- Use same-origin React/Laravel sessions and durable polling first; real event sequences permit reconnect without WebSocket infrastructure or invented percentages.
- Narrow trusted QA instruction/adapter registry, versioned with compatibility and provenance; defer an extensible runtime skill marketplace and autonomous skill learning. This is a proposed MVP deviation from full Runtime QA scope requiring owner approval, not R1 equivalence.
- No payment processing, checkout, paid-plan activation, automatic upgrade/overage, pricing decision, commercial real-user trial claim or BYOK. Use explicitly approved noncommercial evaluation allowances with separate Scan/Fix ledgers. Existing development-only synthetic settlement cannot be promoted to actual-run settlement.
- Basic=10 and Pro=30 monthly scans are approved full-product values; Team cap, Fix allowances, prices, retention days and retry limits remain unapproved. Evaluation allowances must be separately specified, not inferred from examples.
- Defer optional separately approved unverified Draft PR publication (PD-032). Failed verification yields a private human handoff only. No raw patch publication through a convenience endpoint.
- Authenticated browser checks use one reviewed test-account flow first; no CAPTCHA/MFA bypass, production purchases/deletes or credentials sent to AI/repository scripts.
- Basic evidence export and comparisons replace elaborate report customization. Dashboard and Email remain the only product notification channels; existing Telegram tooling is unrelated.

## Milestones and exit gates

| Milestone | Tasks | Exit evidence | Indicative engineer effort |
|---|---|---|---|
| M0 Reviewable baseline and authorization | 01–02 | Owner-approved scope/policies, preserved dirty baseline, reproducible targeted verification plan | 3–5 days |
| M1 Identity and repository onboarding | 03–07 | Real OAuth/App flow, four-role/two-tenant denials, revocation and no clone on connect | 6–10 days |
| M2 Secure execution foundation | 08–12 | Private evidence, atomic quota, vetted isolation, SSRF/cleanup proof, durable job/fencing tests | 8–15 days |
| M3 GitHub → real Scan → Report | 13–20 | Actual build/test/security/browser/AI results, safe login, no fabricated outcomes | 10–16 days |
| M4 Approval → Fix → Draft PR | 21–25 | Scoped consent, verified patch, real idempotent publication and uncertain-effect reconciliation | 8–12 days |
| M5 Usable evaluation product | 26–29 | Real UI/history/notifications, lifecycle, cost and recovery evidence | 6–10 days |
| M6 Security review and controlled pilot | 30–32 | Independent adversarial review, benchmark evidence, SG coverage, owner Go/No-Go | 6–10 days |

Estimate: 47–78 engineering days plus owner/provider review lead time, roughly 10–16 working weeks for one engineer with prompt reviews. This is a planning range, not an AI speed or delivery guarantee. Isolation/provider access and existing-code failures can change it. One task is implemented and reviewed at a time; milestone integration checks follow relevant task checks.

## Minimum acceptance for the independent MVP

1. All 12 requested feature areas above demonstrate actual behavior; absent integrations are BLOCKED, never simulated PASS.
2. Both supported fixture profiles complete GitHub→scan→private report; negative fixtures expose expected defects with provenance and clean fixtures support measured false-positive review.
3. Source SHA and live deployment identity are separate. Unknown deployment revision is labeled unknown; the report never claims the website matches the checkout without evidence.
4. All ND01 grants and ND13 independent controls are enforced server-side at admission, dispatch and effect boundaries, including revocation races.
5. Actual isolated execution enforces network/SSRF/resource controls and cleanup after success, failure, cancellation and worker loss; unavailable isolation blocks execution.
6. Private evidence passes cross-tenant, expiry, revocation, malicious-output and screenshot/privacy tests. Raw secrets and complete private repositories are not normal retained product data.
7. AI has no authority to grant permissions, execute arbitrary tools, access credentials or publish code. Model outages produce honest partial/blocked analysis, not high-confidence fixture diagnoses.
8. Each fix/PR is authorized to current actor/repository/SHA/plan/files/jobs/budget, revalidated before effects; patch and regression checks run on the exact proposed revision. Stale approval fails closed.
9. Atomic independent Scan/Fix ledgers and logical-run dedup survive concurrent requests, retries and crashes. No automatic overage; no charge per internal test or retry.
10. Owner-approved operational policies, provider/privacy controls, backup/restore and incident evidence exist. All SG-01–SG-12 have scoped evidence; no unresolved exploitable critical/high findings in agreed scope.
11. MVP-31 supplies independent fixture/quality evidence, MVP-32 records a narrow authorized pilot and explicit release decision. Commercial/public release additionally needs deferred commercial, provider and legal requirements; this plan never waives them.

## Decision gates before implementation

MVP-01 must approve the independent evaluation scope and deviations, essential privacy/provider use, evidence classes for real execution, noncommercial allowance/settlement rules, retention/link/secret lifetimes, runtime versions, budgets/retries, target ownership proof, approved tools/model/email/isolation provider and cost ceilings. Decisions can close for the next task only. ND01/ND13 are already approved policies; validate their mechanisms rather than reopening their grants. ND02/03/04/05/06/07/09/10/11/12/14/15 and QTD-01–04 are assessed by applicable MVP slice; commercial-only decisions remain deferred and cannot authorize public launch. ND08 AI-application evaluation remains later-platform scope; MVP review quality still needs its own approved benchmark thresholds.

See [tasks](MVP_TASKS.md), [architecture](MVP_ARCHITECTURE.md), [progress/evidence](MVP_PROGRESS.md), [agent handoff](AGENT_WORKFLOW.md) and [skills inventory](SKILLS_RECOMMENDATIONS.md). Stop after these planning documents until owner approval.
