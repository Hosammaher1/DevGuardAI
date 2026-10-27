# Codex → Antigravity MVP workflow

Status: APPROVED WORKFLOW UPDATE (2026-10-27). Strict operational boundaries: Antigravity exclusively owns application implementation, test creation, safe test execution within permitted boundaries, debugging, validation, and MVP_PROGRESS updates. Codex serves as a lightweight delegator/orchestrator only, with no Codex fallback for application implementation or test execution.

## Strict Responsibilities

- **Owner**: Approves scope/deviations, product/security/provider policies, task execution, tests/installations/remote writes where needed, pilot and release. Only the owner can relax scope; security invariants remain mandatory.
- **Codex**: Lightweight orchestrator/delegator. Freezes bounded handoff scope, passes handoff prompts to Antigravity, and tracks high-level operational status. Codex does not execute application tests or implement code changes.
- **Antigravity**: Exclusively owns all application code editing, test file creation, test execution (within authorized sandbox rules), debugging, and documentation progress ledger updates (`docs/MVP/MVP_PROGRESS.md`). All tests must be executed and reported by Antigravity directly; if test execution is blocked by environment or sandbox boundaries, the task is marked BLOCKED/PARTIAL and never claimed IMPLEMENTED or GREEN.
- **Platform operator**: Separately provisioned emergency authority under ND13. Neither agent nor workspace Owner inherits it.

## Execution Rules & Tooling Confinement

1. **Native File Tools**: Antigravity uses native file read and edit tools as the primary mechanism for all source code, migration, test fixture, and documentation edits.
2. **Sandbox Rules**: Antigravity runs inside standard sandbox boundaries without `--dangerously-skip-permissions` or manual bypass. No attempts may be made to expose host `/mnt/c`, alter container/sandbox mountings, or widen permissions.
3. **Command Policy**: Only explicitly whitelisted Project-scoped rules (defined in `docs/MVP/execution-evidence/CLI_PERMISSIONS.md`) may be executed. If Windows runtimes (e.g., `php.exe`) are not accessible inside the Linux container (exit 127), the test run is recorded as `BLOCKED (sandbox runtime unavailable)` and must NOT be retried outside isolation or guessed.
4. **No Fallback / No Speculative Claims**: No test PASS or GREEN status is claimed without real, direct execution evidence. Tasks with blocked execution or unverified external dependencies remain strictly `PARTIAL` or `BLOCKED`.
5. **No Repository Mutation**: No commits, pushes, rebases, deletes, or remote writes. Preserves baseline dirty files and user-owned files.

## Handoff preconditions

Before invocation: approved MVP scope/spec; task/dependencies accepted; safe baseline including treatment of user-owned untracked work; working authenticated CLI probe; verified developer confinement and permitted shell/network effects; frozen exact file allowlist and commands; test/fixture/data/provider permissions; no overlapping user edit. Worktree creation and any application dependency setup are future actions requiring authorization; do not stash/reset or copy secrets to create a baseline.

If the current Windows environment is later used, validate its actual terminal restrictions independently. The native Linux CLI detected in this session does not prove Windows isolation. A --sandbox flag and a prompt are not sufficient isolation evidence. Product execution additionally requires its separate external runner boundary.

## Concrete handoff template

```text
TASK: MVP-xx; attempt 1; implementation authorization reference: <owner decision>
ROLE: Antigravity implementation engineer; Codex is reviewer.
WORKSPACE: <approved isolated absolute path>; BASE: <commit + approved dirty snapshot digest>
READ: AGENTS.md, docs/MVP/MVP_PLAN.md, MVP_ARCHITECTURE.md,
      MVP_TASKS.md section MVP-xx, approved slice acceptance and policy versions.
DEPENDENCIES: <accepted task/evidence refs>
ALLOW EDITS: <exact files>; DO NOT TOUCH: original docs, unrelated dirty files, secrets.
INTERFACES: <frozen request/response/schema/transition definitions>
ACCEPTANCE: <case IDs, Given/When/Then, negative and concurrency oracles>
ALLOWED COMMANDS: <exact commands/cwd/environment/time limits; test authorization>
EXTERNAL EFFECTS: <none or explicitly permitted test-provider/test-repository operations>
BUDGET: <approved task time/attempt/cost constraints>
STEPS: correct RED → minimum implementation → GREEN → refactor → relevant checks.
RETURN: actual changed paths/diff summary; commands/exits/results; provenance;
        private sanitized evidence refs; cleanup; blockers and residual risks.
STOP: after this task; no next task, install, commit, push, deployment or scope expansion.
```

Never place API keys, raw private customer code or sensitive logs in command arguments. Feed an approved nonsecret prompt through supported stdin or a safely quoted argument; use repository artifact references for details. Structured output is desirable but schema shape must first be checked against actual installed CLI response, not invented.

## Direct CLI invocation and observed limitations

Verified locally: `/home/hp/.local/bin/agy --version` → 1.4.8; `agy --help` confirms --print, --mode, --sandbox, --print-timeout and output formats. Headless support is also documented in [official Antigravity headless guidance](https://antigravity.google/docs/cli/headless/). Launch success does not prove authentication/model response or enforcement.

The no-tool probe attempted was:

```bash
agy --mode plan --sandbox --disable-slash-commands --print-timeout 30s --print 'Read-only connectivity probe. Use no tools, read no files, run no commands, and change nothing. Reply exactly DEVGUARD_READ_ONLY_OK'
```

Outer sandbox prevented home runtime logs/local listener. An escalated retry ran but warned plan mode is ineffective with slash expansion disabled, then timed out with partial output, exit 0 and no requested response. That attempt did not verify authenticated direct delegation. It was not a coding handoff or a successful response test. Runtime log creation is a CLI side effect, not an application-file modification.

Corrected no-tool probe, subsequently executed from `/tmp` with narrowly approved outer-sandbox escalation:

```bash
agy --mode plan --sandbox --print-timeout 30s --print 'Read-only connectivity probe. Use no tools, read no files, run no commands, and change nothing. Reply exactly DEVGUARD_READ_ONLY_OK'
```

Observed result: exactly `DEVGUARD_READ_ONLY_OK`, exit 0, no partial/timeout marker, and unchanged outside-MVP project fingerprint. This verifies launch and model response only. For future environment changes, run from an approved disposable/nonproject directory with normal authentication and permitted runtime paths; require a complete model answer, no timeout/partial marker, successful exit and unchanged project fingerprint. Investigate authentication/model availability through ordinary CLI guidance; do not inspect/copy credential files or bypass permissions. If the outer sandbox blocks necessary runtime behavior, request a narrowly explained escalation, never silently disable confinement. Before claiming working implementation delegation, separately verify coding-mode tools, approval handling and effective confinement; a successful no-tool response alone does not prove those capabilities.

Future implementation command shape, only after explicit task authorization and verified confinement:

```bash
agy --mode accept-edits --sandbox --print-timeout <approved-duration> --output-format json --print '<approved nonsecret bounded handoff>'
```

This shape is supported by help but has not been verified as a coding workflow. Validate actual structured response and permission handling first; if tools require interactive approval, use the supported interactive CLI with human decisions. Do not use --dangerously-skip-permissions, auto mode, --continue/latest session without verified identity, new MCP servers, n8n, Telegram, wrappers or orchestration scripts. The existing TASK.md/tooling workflow is not used or modified. Never use a developer CLI directly as the SaaS customer-code runner.

## Review and acceptance cycle

1. Capture baseline status and hashes; submit the exact authorized handoff directly to Antigravity. Track one process/conversation and bounded task; no concurrent implementation writers.
2. Capture actual stdout/stderr/exit, timeout/completion markers and returned conversation/task identity. Timeout/exit 0 without completion is INCOMPLETE. Do not immediately resend and risk duplicate work; determine existing process/task state first.
3. Inspect diff/status against baseline, including untracked files, dependency/lockfile changes, generated artifacts and scope. Unauthorized edits stop acceptance; preserve evidence and ask owner how to recover user-owned overlaps. Never reset/discard automatically.
4. Review behavior against acceptance and SG/ND01/ND13. Check real identity/target/SHA binding, transactional concurrency, AI privilege boundaries, evidence sanitization and GitHub effect reconciliation as applicable. Codex reads implementation, not merely Antigravity's summary.
5. Run only authorized relevant checks in safe environment; actual integration required for isolation, provider/GitHub, quota/concurrency and recovery claims. Record evidence class/versions/limitations. Application tests were not authorized or run during planning.
6. ACCEPT only when all task criteria/checks and scope review pass; otherwise issue a precise fix request with observed failure, affected files and unchanged acceptance. Acceptance never commits/pushes automatically.
7. Update only MVP progress for this track. Proceed to another task only after its dependencies and explicit authorization are satisfied. Do not silently update original roadmap tasks from MVP results.

## Error recovery and retry proposal

Proposed manager limit: initial handoff plus at most two targeted correction handoffs for the same approved task, then stop for owner review. This is a workflow proposal requiring approval; it is unrelated to the unapproved product AI Fix retry cap. Stop earlier on security/scope violation, missing isolation, unapproved policy/provider, secret exposure or repeated identical failure. Time/spend limits also apply once approved.

Retain failed task state and sanitized investigation evidence. No force push, reset, rebase around conflicts or automatic cleanup of user files. CLI/auth failure → report blocker; test failure → meaningful diagnosis/correct failure evidence; flaky test → preserve observations and isolate cause rather than rerun until green; provider uncertain effect → reconcile before retry. Rollback/deployment/remote writes require explicit scope. If independent reviewer skills require another reviewer, arrange authorized review without treating agent count as proof of independence.

Stop after the six planning documents in this session; wait for owner approval before all implementation work.
