# DevGuard AI Local Task Orchestrator — Design Specification

**Status:** Proposed design only. Documentation work does not authorize implementation, script creation, installation, application tests, Git writes or deployment. Existing approved requirements and `AGENTS.md` remain authoritative. The Master Test Plan is specified, not executed.

**Purpose:** A Windows PowerShell orchestrator that advances one explicitly approved, dependency-ready implementation-plan task at a time through Codex CLI, checks its result with independent deterministic gates, and records enough evidence to resume safely.

## 1. Authority and inputs

The task catalog and prerequisite graph come from the current [implementation plan](../plans/devguard-implementation-plan.md). Acceptance IDs, canonical owners, security gates and execution status come from the [Master Test Plan](../tests/devguard-master-test-plan.md). Security and workflow constraints come from the [R1 Engineering Blueprint](devguard-r1-engineering-blueprint.md) and [AGENTS.md](../../../AGENTS.md). These documents are inputs; the orchestrator does not edit them or infer approval from a task appearing in a plan.

Before execution, a human-approved task record must bind the exact task ID and plan revision, its prerequisites, approved scope, required acceptance IDs and quality gates, Codex CLI invocation policy, maximum usage, and the requested push target `origin/dev`. A broad approval for “R1” is insufficient. Plan/source changes invalidate affected approvals and require review again.

The task reader should load only the selected task, its transitive prerequisites, owned acceptance cases, relevant security gates and applicable AGENTS instructions. Cache content by file revision/hash and record the hashes used; refresh only affected material when inputs change. Do not send the full test plan or PRD to Codex for every task.

## 2. Components and sequential workflow

The design has four logical components:

1. **Plan reader and readiness checker:** parse task IDs, prerequisites, canonical test ownership and release markers from approved documents; reject missing, duplicate, cyclic, or ambiguous records. A task is ready only when each prerequisite has a recorded verified completion at the required plan revision.
2. **Approval and policy gate:** confirm a task-specific human approval, clean isolated task workspace, allowed scope, usage ceiling, unchanged source revision, expected destination, and no unresolved safety stop. An approval prompt from Codex CLI is a stop-and-wait condition, never an instruction for the orchestrator to approve automatically.
3. **Codex CLI task runner:** invoke one approved task with a bounded prompt containing only its task contract and relevant source excerpts. Run tasks sequentially; no background task fan-out or automatic task expansion. Use the normal Codex sandbox and approval configuration. Never use flags, environment overrides, wrappers, or alternate accounts to weaken sandbox or approval controls.
4. **Independent verifier and Git publisher:** run the preapproved deterministic gates directly, inspect the exact task diff and evidence, then commit and push only that verified task change. Codex’s completion statement is not verification evidence.

For each task: validate approval and readiness → verify and record the current `dev` base SHA → create an isolated `task/Txx` branch from that verified `dev` state → invoke Codex CLI → stop on interruption, failure or approval request → run independent gates → inspect changed paths and diff → commit only intended verified changes on `task/Txx` → confirm `dev` has not changed since the recorded base → integrate the task into `dev` → push to `origin/dev` → confirm the remote ref resolves to the verified task commit → persist completion and evidence → consider the next ready, separately approved task.

The runner must stop immediately on a failed quality gate, missing or stale approval, unexpected file change, security concern, unexpected provider effect, usage limit, CLI/sandbox error, conflict, concurrent change to `dev`, non-fast-forward push, branch-protection requirement, or unavailable verification evidence. It records the stop reason and resumes only after the blocking condition is resolved and any invalidated approval is renewed. It never auto-retries a failed implementation, silently skips a prerequisite, rebases, force-pushes, bypasses branch protection, merges to `main`, or continues to the next task before integration and push succeed.

## 3. Independent quality gates

Gates are selected from the task’s acceptance IDs, task-specific plan, Master Test Plan and applicable security gates. The approved gate record names exact deterministic commands and expected evidence before the task starts. Depending on the task, gates may include relevant automated acceptance tests, lint/type checks, build checks, static/security checks, schema or traceability validation, and change-scope checks. Run only relevant checks per task; run broader regressions at the plan’s integration gates.

Gate processes run outside Codex’s self-report path and preserve command, version, exit status, concise output and artifact hashes. Security-sensitive tasks require the applicable P0/security acceptance evidence and independent review; no passing linter or documentation check substitutes for authorization, tenancy, billing, sandbox, secrets, GitHub, recovery or Runtime QA evidence. Any unavailable, skipped, flaky or inconclusive required gate is not a pass and blocks commit/push.

## 4. Git safety and destination

Create one isolated `task/Txx` branch per approved task from the current verified `dev` commit. Keep all task edits and the task commit on that branch until independent gates pass. Stage explicit authorized paths only. Before integration, confirm the task diff contains no unauthorized files and `dev` still points to the recorded base; if it advanced, preserve the task branch and stop for human resolution. Do not rebase, force-push, or merge around a conflict.

When the base is unchanged and gates pass, integrate the task into `dev` and push the resulting verified commit to `origin/dev` without force. The normal no-conflict path is a fast-forward; any different integration requirement stops for review. Confirm the remote `dev` ref resolves to the verified task commit before marking the task complete or starting another task. Never automatically push or merge to `main`.

Respect GitHub branch protection. If updating `dev` requires a pull request or another approval, stop with the verified task branch and evidence intact. Wait for the required review/approval through the protected workflow; do not bypass protection or make an unapproved task-branch publication. After approval/integration, verify `origin/dev` contains the verified task commit before resuming. Preserve failed task branches and their investigation logs; do not continue to the next task unless integration and push succeed.

## 5. Dry run, state and evidence

Dry-run mode parses the plan, validates IDs/dependencies, reports which tasks are ready, blocked, or missing explicit approval, and prints the planned gates and proposed Codex prompts. It launches no Codex implementation task and makes no Git writes: no checkout/worktree creation, fetch, branch/ref update, staging, commit, push or Git configuration change. It performs no application tests. It must label any readiness result as a local snapshot, not current remote state.

Persist run state outside the repository by default, under a per-user local application-data directory with user-only access. Keep a versioned state record containing run ID, selected task and state, dependency results, source/plan hashes, approval receipt and scope, base and task commit IDs, gate commands and versions, outcomes, usage counters, stop reason, remote verification, and evidence references with hashes. Keep bounded/redacted logs; never persist secrets, raw credential-bearing environments, full repository contents, or unfiltered Codex context. Use atomic state updates and validate state/schema/hashes before resume. If state is missing, corrupt, inconsistent with the plan or repository, or cannot prove what was approved and verified, require review instead of guessing.

Task states: `BLOCKED`, `READY`, `APPROVAL_REQUIRED`, `RUNNING`, `VERIFYING`, `STOPPED`, `COMMIT_PENDING`, `PUSH_PENDING`, `VERIFIED_COMPLETE`. Only the final state satisfies downstream dependencies. A crash during commit or push is reconciled by comparing local and remote commit identities; uncertain state stops for review and never blindly repeats a write.

## 6. Usage and stop controls

Every task has a human-approved usage ceiling covering available Codex usage signals, wall time, and allowed verification commands. Track actual reported usage and stop before starting another task when the ceiling is reached or cannot be measured reliably. Do not change models, providers, reasoning settings or billing arrangements to evade a ceiling. Do not trade away security checks or required test coverage to reduce usage.

## 7. Acceptance and unresolved design risks

The orchestrator is acceptable for implementation planning only when task parsing matches the approved plan, prerequisite readiness uses canonical ownership, approval binds the exact task/revision/scope, stop conditions are fail-closed, gates are independent, dry-run produces no implementation or Git writes, resume rejects stale/corrupt state, and the publisher can prove the pushed SHA is the verified task commit. These are future validation criteria, not completed tests.

Unresolved before implementation or operational use:

- **Protected-branch handling:** define the exact pause/resume handoff when `dev` requires a pull request or other approval; never bypass the configured protection.
- **Concurrent-change recovery:** define human-reviewed resolution and renewed verification when `dev` advances after a task branch is created; never auto-rebase or merge conflicts.
- **Codex CLI contract:** pin and verify supported invocation, structured output/usage reporting, interruption behavior and sandbox/approval configuration for the chosen CLI version; do not assume undocumented flags.
- **Approval record:** select a tamper-evident, human-reviewable format and define approval expiry/revocation and task-scope matching.
- **Independent gate catalog:** map each R1 package to exact commands, environments and required evidence without executing application tests until separately authorized.
- **Plan parser resilience:** confirm stable headings/IDs and fail-closed handling for changed or malformed Markdown; avoid silently accepting stale cached content.
- **State and workspace recovery:** validate local state permissions, atomic writes, crash recovery, temporary worktree cleanup and Git remote identity checks.
- **Usage observability:** confirm available Codex CLI usage signals and define safe ceilings without selecting a paid provider or commercial value.

No new product requirement, task ID, acceptance ID, provider choice, commercial value or permission to implement is introduced by this specification.
