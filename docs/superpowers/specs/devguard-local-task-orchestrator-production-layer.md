# DevGuard AI Local Orchestrator — Durable Execution Layer Design

**Status:** Proposed technical design; not implemented. The existing prototype remains dry-run only. This document authorizes no task execution, application tests, Git writes, dependency installation, or deployment. All acceptance cases below are `SPECIFIED / NOT EXECUTED` and use synthetic task data and fake Codex/Git/gate adapters.

**Scope:** The next local Windows PowerShell orchestration layer: crash-safe task state, immutable task-specific approvals, independent verification, isolated task branches, safe stop/recovery behavior, and strict separation from the existing dry-run prototype.

**Authority:** Follow the [approved local task orchestrator specification](devguard-local-task-orchestrator.md), the current implementation plan, Master Test Plan, R1 Engineering Blueprint and [AGENTS.md](../../../AGENTS.md). The approved task/Txx branch workflow, `dev` integration target, branch-protection rules and prohibition on automatic `main` updates remain unchanged. This design makes no product, commercial, provider or permission decision.

## 1. Goals and invariants

The orchestrator advances only an explicitly approved, dependency-ready task, one at a time. A task reaches `VERIFIED_COMPLETE` only after its approval, source revisions, isolated branch, required independent gates, authorized diff, commit, `dev` integration and remote `origin/dev` SHA have all been reconciled and recorded.

The following invariants apply at every transition:

1. Persist transition intent before performing an external side effect, and persist its observed result before proceeding to another side effect.
2. Treat missing, malformed, stale or contradictory state as `STOPPED_NEEDS_REVIEW`; never infer completion from a log line, Codex response or branch name.
3. Never automatically repeat a task invocation, commit, integration or push whose outcome is uncertain.
4. A failed, skipped, timed-out, unavailable or inconclusive required gate is not a pass.
5. No task can proceed unless a verifiable approval binds its immutable task identity, exact scope, plan version and approver identity.
6. `dev` is the only automatic integration destination. Never automatically push or merge to `main`, bypass branch protection, force-push, rebase around conflicts, or continue after an integration/push stop.
7. Dry-run has no execution or Git-write capability. The current prototype remains dry-run only; a future execution package is a separately authorized deliverable, not a mode switch or runtime flag added to the prototype.

## 2. Components and interfaces

Keep components narrow and exchange versioned records rather than mutable shared objects:

| Component | Responsibility | Trust boundary |
|---|---|---|
| Plan snapshot reader | Read the approved task row, prerequisites, canonical acceptance ownership and applicable policy revisions; calculate content digests. | Repository content is untrusted input. Reject ambiguous or changed plan data. |
| Approval verifier | Validate the trusted approval receipt and its signature, identity, expiry/revocation state, and exact task-contract digest. | Trusts only a configured authority and trust roots; never trusts queue fields or Codex output as approval. |
| Task state store | Commit versioned state transitions and an append-only event journal atomically; serialize writers and recover after interruption. | Local user profile data; state corruption or permission failure stops execution. |
| Policy/readiness evaluator | Check dependency completion, approval freshness, source hashes, budgets, allowed files, branch rules and safety stops. | Pure deterministic decision logic; no Codex or Git writes. |
| Execution coordinator | Orchestrate the approved state machine and invoke typed adapters only at permitted transitions. | No shell-built commands; explicit argument arrays and fixed executable paths. |
| Codex adapter | Invoke one bounded task using the configured Codex CLI and its normal sandbox/approval settings; return structured process evidence. | Codex is untrusted with respect to completion claims and produced repository changes. |
| Gate runner | Run the preapproved deterministic gate manifest outside the Codex process and collect machine-verifiable evidence. | Gate runner and evidence are independent of Codex self-report. |
| Git adapter | Create the task branch, inspect diff, commit, integrate into `dev`, push to `origin/dev`, and reconcile refs. | Git/GitHub are external side-effect boundaries; each write requires persisted intent and post-operation verification. |
| Recovery reconciler | Compare durable state with process, worktree, commit and remote-ref evidence after restart. | Uncertainty or conflicting evidence stops for human review. |

The coordinator consumes only typed adapter results (`Succeeded`, `Failed`, `Interrupted`, `RateLimited`, `Unknown`) with exit status, timestamps, relevant request/operation IDs and evidence digests. It does not execute command strings from the plan or approval record. Executables and arguments are selected from an allowlisted, versioned policy record; untrusted repository content cannot change adapter configuration.

## 3. Durable state and crash recovery

### 3.1 Store proposal

Use a per-user store under `%LOCALAPPDATA%\DevGuardAI\orchestrator\`, protected by a restrictive Windows ACL for the current user and administrators. Store bounded metadata and redacted logs only; never persist credentials, tokens, raw credential-bearing environments, or full prompt/repository contents. The exact retention period for personal approval identity and logs remains subject to product-owner approval (see §10).

The store has two logical records:

- **Current snapshot:** schema version, monotonically increasing sequence, run/task IDs, current state, plan/task/scope digests, approval receipt reference/digest, dependency states, branch/base/commit/remote SHAs, gate manifest and results, usage counters, stop reason, timestamps and evidence references.
- **Append-only event journal:** event ID, sequence, prior-event digest, event type, task/run IDs, sanitized payload, evidence digest and timestamp. Never rewrite prior events; append and snapshot update are one transaction.

State storage must provide atomic durable transactions and single-writer serialization. Preferred implementation candidate: a local SQLite database with transactions, a write-ahead log and synchronous durability configured and verified for the target Windows filesystem. Before choosing it for implementation, validate dependency distribution, locking behavior, backup/recovery and power-loss guarantees. A same-volume temp-file + flush + atomic replace design is an alternative only if it can provide equivalent multi-record atomicity, locking, and crash-recovery evidence. No choice is made here without that validation.

Every transition executes as: acquire per-user single-instance lock → reload and validate current sequence/schema/hash chain → verify expected prior state and compare-and-swap sequence → append event and update snapshot in one durable transaction → release lock. If the lock cannot be acquired, do not start another run. On write/flush/replace failure, stop before external effects. The journal must detect truncated records, duplicate sequence numbers, broken hash links and snapshot/journal disagreement; never silently repair by dropping events.

### 3.2 State machine

The durable state machine extends the approved specification’s states with explicit side-effect boundaries:

`BLOCKED → READY → APPROVAL_REQUIRED → APPROVED → BRANCH_CREATE_PENDING → BRANCH_READY → INVOCATION_PENDING → INVOCATION_RUNNING → VERIFYING → COMMIT_PENDING → TASK_COMMITTED → INTEGRATION_PENDING → DEV_INTEGRATED → PUSH_PENDING → REMOTE_VERIFIED → VERIFIED_COMPLETE`

Any state can transition to `STOPPED_NEEDS_REVIEW` for safety, integrity, authorization, concurrency or unknown outcomes. Definite policy stops may use `STOPPED` with a reason and may resume only after the stated condition is resolved and approval/policy are revalidated. `VERIFIED_COMPLETE` is terminal for that task contract; changing task scope or plan version creates a new approval and task attempt.

Before calling Codex, record the intended invocation ID, task contract digest, branch/worktree path, command-policy digest and usage budget. A crash in `INVOCATION_PENDING` is safe to reconcile as not started only when process-start evidence proves no process was created. A crash in `INVOCATION_RUNNING` or any ambiguous result becomes `STOPPED_NEEDS_REVIEW`; inspect the preserved task branch and worktree, do not reinvoke Codex automatically. Interrupted or failed work is never marked complete.

Before each Git write, store operation intent and expected refs/tree. Afterward, query local/remote state and store the observed result. On restart:

- **Branch create:** verify that the branch points to the approved base SHA. A branch with a different tip or unapproved content stops for review.
- **Commit:** compare `HEAD`, parent SHA and tree digest with the recorded intended commit. If exact match, record observed commit; if absent and no commit was created, require explicit resume approval before retry; if conflicting/ambiguous, stop.
- **Integration:** verify `dev` points to the expected integrated commit and that it contains exactly the verified task tree. Conflicts or concurrent `dev` movement stop without rebase/merge attempts.
- **Push:** read the remote ref and compare it with expected base and verified commit. If it equals the verified commit, record success. If it still equals the expected base, a human-approved retry may be proposed after revalidating all gates and approval. Any other ref, unavailable remote, or uncertain result stops for review. Never blindly retry a push.

Recovery is reconciliation, not replay. Only deterministic, read-only inspection may run automatically during recovery. Re-running a write requires a persisted proof that the prior write did not occur, current approval, unchanged policy/source digests, and an explicit resume authorization where the outcome or task execution is uncertain.

## 4. Immutable task contract and approval

Construct a canonical task contract before requesting approval. It includes:

- immutable implementation-plan task ID and task-row digest;
- plan version/content digest and relevant AGENTS/policy revision digests;
- repository identity, approved source `dev` SHA, task branch name and target `dev`;
- exact work-package description, dependency IDs and verified dependency completion digests;
- allowed changed-path patterns and forbidden paths;
- canonical owned acceptance IDs, required gate-manifest digest and permitted deterministic commands;
- usage/time ceilings and permitted execution environment/profile;
- approval authority, approver identity, issued time, expiry, unique approval ID and revocation-check reference.

Canonicalize fields with a documented schema and stable serialization before hashing. The approval receipt contains the resulting contract digest, task ID, plan digest, approver identity, authority key ID, nonce, issue/expiry times, and a signature verifiable against configured trust roots. Reject unsigned, unknown-key, expired, revoked, replayed, malformed or scope-mismatched receipts. Never infer approval from a task’s presence in the queue, a local editable boolean, an email/display name, or Codex output.

Revalidate the receipt and all bound digests immediately before branch creation, Codex invocation, commit, `dev` integration and push. If scope, plan, source base, policy, gate manifest, target branch or approver authority changes, stop and obtain a new approval. Approval for one task never authorizes its dependency or successor. The authority and its identity proof are pluggable interfaces; no provider, paid service or identity source is selected by this design. A local prototype marker is never accepted as a production approval.

## 5. Independent machine-verifiable gates

Every task must have a signed or otherwise trusted gate manifest approved with the task contract. Each required gate declares a stable gate ID, requirement/acceptance IDs, security or quality class, exact executable and argument array, tool/version or binary digest, working directory, environment allowlist, timeout/usage ceiling, expected exit/result schema, and evidence files/digests. No shell interpolation, command supplied by a repository file, or Codex-created gate may execute.

Run each gate in a separate process outside the Codex task process, with a clean environment and no inherited secrets. Capture exit code, tool version, bounded output, timing, artifact digests and runner identity. Deterministic assertions decide pass/fail; AI-generated review may add advisory findings but cannot make a required gate pass. Security-critical acceptance gates remain mandatory even under budget pressure. Missing command/tool, unsupported host, timeout, crash, skipped gate, malformed output, changed manifest or inconclusive result means stop without commit or push.

Verify the exact worktree diff against the approved path policy before gates and again before commit. A gate pass binds to the task contract digest, source/base SHA, worktree tree digest, gate-manifest digest and evidence digest. Any content change invalidates affected evidence and requires rerunning the gates. Broader regressions run only at defined integration gates; they cannot replace task-owned required checks.

## 6. Isolated branch and `dev` integration

Use the approved hybrid, task-isolated workflow:

1. Confirm the current verified `dev` SHA equals the approved base; create exactly `task/Txx` from that SHA in an isolated worktree. Never reuse a failed task branch for a different approval contract.
2. Run one task invocation on that branch only. Preserve branch/worktree and bounded redacted logs on failures.
3. After independent gates and path checks pass, revalidate approval and persist commit intent. Commit only explicit authorized paths; verify resulting parent, tree and commit SHA.
4. Recheck `dev` has not advanced from the approved base. If changed, stop and preserve evidence. Do not rebase or resolve conflicts autonomously.
5. Integrate only the verified commit into `dev` without force. Respect branch protection. If a pull request or approval is required, stop and wait for it; do not bypass protection.
6. Push only to `origin/dev`, then read and verify the remote ref equals the verified integrated commit. Mark `VERIFIED_COMPLETE` only after that evidence is durable.
7. Do not start another task until integration and remote verification have succeeded.

Every step uses explicit Git argument arrays and validates repository root, remote identity, branch/ref names and current SHA. Git hooks or subprocess errors are not skipped. Unexpected files, submodules, symlinks, generated secrets, concurrent changes, conflicts, non-fast-forward responses, protected-branch rejection or remote mismatch stop the workflow. No automatic path targets `main`.

## 7. Codex failures, limits and interruptions

The Codex adapter uses only the supported CLI contract validated for the selected version, normal sandbox and approval settings, and no bypass flags or environment overrides. Record CLI version, invocation ID, start/end, process exit, structured status when documented, and usage values that the CLI reliably exposes. Do not treat natural-language “done” as success.

- **Nonzero exit or explicit task failure:** record failure and evidence; stop and preserve branch. Do not auto-retry implementation.
- **Rate limit with documented rejection-before-acceptance:** record `STOPPED_RATE_LIMITED` and any server-provided retry-after. Do not poll or retry autonomously; require an explicit resume after cooldown, refreshed usage availability, unchanged contract and valid approval.
- **Rate limit or transport failure with ambiguous acceptance:** mark invocation outcome unknown and require human reconciliation; never issue a duplicate task invocation.
- **Usage ceiling reached or usage signal unavailable/unreliable:** stop before starting another invocation or gate that consumes the bounded resource. If required security gates cannot run, do not commit/integrate/push.
- **User interruption, process termination or machine restart:** durably mark `INTERRUPTED` if the process can do so; otherwise recovery infers an unknown in-flight state and stops. Preserve evidence; no automatic rerun.
- **Approval prompt, sandbox denial, security concern, unexpected credential/provider effect, or child-process escape:** stop immediately, record sanitized evidence, and require review. Never auto-approve or weaken policy.

The exact usage signal and measurement reliability are technical validation items. The system must fail closed when usage cannot be bounded; it must not silently change provider, model or account.

## 8. Strict dry-run / future execution separation

The existing prototype remains the only authorized runnable orchestrator and remains dry-run only. It may parse approved local documents, validate synthetic queue data and print proposed actions; it must not load or contain a Codex process adapter, Git write adapter, mode toggle, dynamic command loader, or code path that can create branches, commits or pushes.

If the product owner later authorizes implementation of real execution, build it as a separately reviewed package/entry point with separate installation, policy, state schema and approval verifier. It must not be enabled by a `-Mode`, environment variable, queue field, config switch, scenario name or edited fixture. Dry-run must continue to use only fake/in-memory adapters and synthetic records. The real package must require a separately authorized operator invocation and validated approval/gate configuration; missing or contradictory configuration fails closed. The production package is not part of this request and must not be scaffolded or activated now.

Synthetic acceptance tests must prove separation structurally as well as behaviorally: dry-run cannot resolve/import the production adapter package, has no execution-mode parameter, produces zero fake process/Git write calls, and reports only “planned/simulated” outcomes. Static tests inspect the source/AST and dependency graph for disallowed invocation paths. These checks are themselves future synthetic tests, not run in this documentation task.

## 9. Synthetic acceptance test contract

All cases below are owned by the **Local Orchestrator Acceptance Harness**, use temporary synthetic state and fake adapters, and are `SPECIFIED / NOT EXECUTED`. They do not execute application tests or real Codex/Git commands. `FakeCodex`, `FakeGateRunner`, and `FakeGit` record calls and return configured deterministic results. State-store crash cases inject failure between writes and reopen the store in a fresh process/context. Passing the future synthetic harness will not establish that production security or external providers are validated.

| ID | Acceptance scenario and required result | Traceability | Status |
|---|---|---|---|
| ORCH-DUR-001 | Atomic transition: inject termination before transaction commit; reopen shows the prior complete sequence and no partial event/snapshot. | Durable state invariants §3.1 | SPECIFIED / NOT EXECUTED |
| ORCH-DUR-002 | Terminate after durable intent but before adapter call; recovery proves no side effect occurred and does not mark task complete. | §3.2 | SPECIFIED / NOT EXECUTED |
| ORCH-DUR-003 | Terminate during Codex invocation; recovery records unknown/in-flight and makes zero second FakeCodex calls. | §3.2, §7 | SPECIFIED / NOT EXECUTED |
| ORCH-DUR-004 | Truncate journal, corrupt snapshot, break hash chain, duplicate sequence or mismatch snapshot and journal; recovery stops for review without deleting or “repairing” evidence. | §3.1–3.2 | SPECIFIED / NOT EXECUTED |
| ORCH-DUR-005 | Concurrent second orchestrator cannot acquire the single-writer lock; it performs no task or Git adapter call. | §3.1 | SPECIFIED / NOT EXECUTED |
| ORCH-DUR-006 | Simulate crash after local commit; reconciler recognizes only an exact parent/tree/commit match and does not create a duplicate commit. | §3.2 | SPECIFIED / NOT EXECUTED |
| ORCH-DUR-007 | Simulate ambiguous push; remote equals verified SHA → record success; remote unchanged at base → stop and request authorized retry; any other SHA or unavailable remote → stop. | §3.2, §6 | SPECIFIED / NOT EXECUTED |
| ORCH-APR-001 | Valid signed receipt binds exact task ID, scope digest, plan digest and approver identity; only exact match reaches `APPROVED`. | §4 | SPECIFIED / NOT EXECUTED |
| ORCH-APR-002 | Missing, unsigned, malformed or unknown-key receipt stops before branch creation and all task/write adapters. | §4 | SPECIFIED / NOT EXECUTED |
| ORCH-APR-003 | Alter task ID, allowed paths, acceptance IDs, budget, target, plan version or base SHA after signing; digest mismatch stops before the next side effect. | §4 | SPECIFIED / NOT EXECUTED |
| ORCH-APR-004 | Expired, revoked or replayed nonce is rejected; approval for a prerequisite/successor does not authorize another task. | §4 | SPECIFIED / NOT EXECUTED |
| ORCH-APR-005 | Change policy, gate manifest, source base or relevant plan digest after approval; revalidation invalidates the receipt and prevents invocation/commit/push. | §4–5 | SPECIFIED / NOT EXECUTED |
| ORCH-GATE-001 | Fake independent gate returns pass with matching manifest/tree digests; exact evidence is stored and bound to the task contract. | §5 | SPECIFIED / NOT EXECUTED |
| ORCH-GATE-002 | Required security/quality gate fails, times out, is skipped, unavailable or returns malformed evidence; no commit, integration or push call occurs. | §5–6 | SPECIFIED / NOT EXECUTED |
| ORCH-GATE-003 | Codex modifies a forbidden path or changes a file after gate pass; path validation or tree-digest binding invalidates gates and blocks commit. | §5 | SPECIFIED / NOT EXECUTED |
| ORCH-GATE-004 | Repository content attempts to add/change executable gate commands or environment; untrusted data is ignored/rejected and no unapproved command reaches FakeGateRunner. | §2, §5 | SPECIFIED / NOT EXECUTED |
| ORCH-GIT-001 | Task branch is created from the exact approved `dev` SHA; wrong base, existing mismatched branch, or unexpected repository/remote identity stops. | §6 | SPECIFIED / NOT EXECUTED |
| ORCH-GIT-002 | Passing gates permit only explicit authorized paths to be committed; unexpected files or changed `dev` cause no integration/push. | §5–6 | SPECIFIED / NOT EXECUTED |
| ORCH-GIT-003 | Conflict, non-fast-forward, branch-protection rejection or required review stops without force, rebase, bypass, `main` target or next-task start. | §6 | SPECIFIED / NOT EXECUTED |
| ORCH-GIT-004 | Fake remote confirms exact verified integrated SHA before `VERIFIED_COMPLETE`; mismatch leaves task stopped and blocks dependent task. | §3.2, §6 | SPECIFIED / NOT EXECUTED |
| ORCH-CLI-001 | Codex nonzero exit or explicit failure preserves task branch/evidence and causes no automatic retry or Git commit. | §7 | SPECIFIED / NOT EXECUTED |
| ORCH-CLI-002 | Explicit pre-acceptance rate limit stops without polling/retry; ambiguous rate-limit/transport outcome enters review and makes no duplicate invocation. | §7 | SPECIFIED / NOT EXECUTED |
| ORCH-CLI-003 | Usage exhausted or unavailable stops before invocation; required security gate cannot be waived to fit the budget. | §5, §7 | SPECIFIED / NOT EXECUTED |
| ORCH-CLI-004 | User interruption, sandbox denial, approval prompt or security concern stops and preserves state; no automatic approval, restart or weakened policy occurs. | §7 | SPECIFIED / NOT EXECUTED |
| ORCH-SEP-001 | Dry-run parses valid synthetic work and reports plans only; FakeCodex, FakeGit and gate execution call counts remain zero. | §8 | SPECIFIED / NOT EXECUTED |
| ORCH-SEP-002 | Static/AST and dependency checks find no execution mode parameter, production adapter import, process launch, Git write API, dynamic command loader or mode-by-environment escape in the dry-run package. | §8 | SPECIFIED / NOT EXECUTED |
| ORCH-SEP-003 | Mutating queue fields to request execution, or supplying a mode-like environment/config value, cannot change dry-run behavior or create side effects. | §8 | SPECIFIED / NOT EXECUTED |

### Test ownership and traceability

The Local Orchestrator Acceptance Harness owns all `ORCH-*` cases. A future implementation plan must assign each case to the state store, approval verifier, gate runner, Git adapter, recovery coordinator or mode-separation package, and run only synthetic tests using fakes. These IDs are local tooling acceptance IDs; they do not replace or modify DevGuard product acceptance IDs in the Master Test Plan. Every production requirement in §§3–8 maps to at least one case above. Real provider/CLI/Git integration validation requires separate authorization and must not be inferred from synthetic results.

## 10. Decisions and validation still needed

### Product-owner decisions

1. **Approval authority and approver eligibility:** choose which human identity authority is trusted to issue receipts, which identities/roles may approve task scope, and who can revoke or renew approval. Until selected, production approval cannot be accepted; no provider is selected here.
2. **Approval lifetime and renewal policy:** set receipt expiry and decide whether explicit resume after rate limit/interruption or a provably absent Git write requires a fresh approval. Safe default proposal: resume requires renewed human confirmation whenever the prior execution outcome is uncertain; do not extend approval automatically.
3. **Retention of approval identity and audit evidence:** set retention/access policy for user identity, task-level logs and recovery records. Safe default proposal: metadata only, bounded redacted output, no credentials or repository contents, with deletion after the approved retention period.

### Technical validation (no product-owner policy choice implied)

- Validate Windows durable-write, locking, ACL and crash semantics for the selected state-store implementation and supported filesystems.
- Validate documented Codex CLI invocation, sandbox/approval preservation, interruption behavior, rate-limit signals, structured exit/result data and usable consumption metrics for the selected CLI version.
- Validate Git plumbing and remote-ref reconciliation for concurrent `dev` changes, interrupted push and protected-branch rejection without bypassing policy.
- Define the deterministic gate catalog and exact tool versions/commands per approved R1 work package; verify evidence formats and tree-digest binding.
- Threat-model local administrator compromise, same-user state tampering, signing-key custody, trust-root rotation and approval-authority outage. The local ACL protects against other unprivileged users; it is not proof against the same user or an administrator.
- Verify logs and approval receipts contain no secrets and that persistence/retention behavior meets the approved privacy requirements.

Until the product-owner decisions and required technical proofs are complete, this remains a design. The existing dry-run-only prototype stays unchanged and no autonomous execution is active.
