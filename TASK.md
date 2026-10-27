# CLI-only Codex → Antigravity → Telegram plan

## Objective and boundaries

Use the installed Windows Codex CLI for planning and final review. Codex invokes
the installed `agy` CLI through a terminal command; Antigravity alone implements
approved code changes. Send factual task-start, implementation-start, completion,
and failure messages through the existing n8n Telegram webhook with direct
PowerShell HTTP requests. Do not add or run scripts, wrappers, Python files,
services, databases, or another workflow. Do not change DevGuard application
files during setup or delete any file without explicit approval.

Current repository facts: branch `feature/direct-cli-telegram` has substantial
pre-existing tracked and untracked application changes. Earlier local notifier
files under `tooling/` remain present and must not be used for this plan. A prior
read-only Codex turn successfully invoked `agy.exe --version` (exit 0, `1.4.8`)
without changing the disposable project's file hashes. A Codex-sandboxed
`agy --mode plan` attempt failed because Antigravity could not access its sign-in
state. Direct PowerShell access to the existing webhook credential has not been
verified. These are execution gates, not completed setup.

## Execution checklist

### 1. Preserve scope and establish the baseline

- [ ] Record `git status --short --branch`, the current branch, and the exact
  task scope before a future run. Treat every existing tracked and untracked
  change as user-owned.
- [ ] Confirm explicit authorization for any DevGuard application task under
  `AGENTS.md`. Until then, use read-only probes only; do not run application
  tests or edit application files.
- [ ] Leave the earlier `tooling/` files untouched and unused. Ask the owner
  before removing or reverting them or the earlier n8n formatting change.

**Test criteria:** Baseline paths and hashes are recorded; setup causes no
application-file changes and no deletion.

### 2. Establish the direct CLI handoff

- [ ] Use Codex CLI to produce a bounded plan in the conversation without
  editing project files.
- [ ] Have Codex invoke `agy.exe` directly from its terminal. First use a
  read-only CLI command (`agy.exe --version`); retain the prior successful
  evidence instead of repeating it unless the environment changes.
- [ ] Check whether `agy --mode plan --sandbox --print ...` can complete from
  the permitted Codex session with normal authentication. Do not expose or
  copy Antigravity credentials, broaden filesystem access to them, or disable
  either CLI's sandbox to make this pass. If it still fails, stop and report
  the authentication boundary.

**Test criteria:** The Codex transcript shows the actual `agy` terminal command,
its exit code, and output; before/after file listings and hashes match for the
read-only probe. A version response alone does not prove authenticated task
execution.

### 3. Establish direct, authenticated Telegram delivery

- [ ] Use only an already available, safely supplied `X-Agent-Key` in the
  current shell session and the existing HTTPS event webhook. Do not inspect
  Router internals, print the key, store it in a file, or use the old notifier.
  If the key is unavailable, report the blocker and stop.
- [ ] Prepare one inline PowerShell `Invoke-RestMethod` POST with a unique,
  stable event ID, task ID, project name, task description, and one of the
  existing n8n event names: `planning_started` for task start,
  `task_started` for Antigravity implementation start, `task_completed` for
  verified completion, or `task_failed` for an observed failure.
- [ ] Send at most one harmless read-only probe event, then require the n8n
  response to match the event ID, say `delivered`, and include a Telegram
  message ID. Reuse the same event ID if delivery must be retried.

**Test criteria:** A matching delivery receipt exists and the Telegram message
contains the factual description or error. If either cannot be verified, do not
claim Telegram delivery.

### 4. Run an authorized implementation task later

- [ ] Recheck project permission and the Git baseline. Stop if the proposed
  edit overlaps an existing uncommitted change or its scope is unclear.
- [ ] Codex plans only. It sends the factual start event and invokes the
  installed `agy` CLI directly with `--mode accept-edits --sandbox` for the
  approved paths. Do not use `--dangerously-skip-permissions` or let Codex
  implement project code.
- [ ] Keep automatic shell and test commands disabled if effective Windows
  confinement is unverified. Obtain explicit approval for any necessary
  consequential command; never treat the CLI sandbox flag alone as proof of
  shell confinement.
- [ ] Codex reviews the actual changed files against the baseline and plan.
  Run only authorized verification and record its real output. If Antigravity
  fails, review rejects, tests fail, or scope is violated, send `task_failed`
  with the observed error.
- [ ] Send `task_completed` only after the final Codex review and real checks
  pass. Include the task description, actual changed paths, actual test names
  and results, and any remaining blockers. Verify the Telegram receipt.

**Test criteria:** Codex has no implementation edits; Antigravity's change set
is limited to approved paths; pre-existing changes remain intact; verification
output and review support every success claim. No completion event is sent for
an unverified or failed result.

## Exit conditions

- [ ] Report the working commands, verified CLI handoff, verified Telegram
  receipt, and any unresolved authentication or sandbox limitation.
- [ ] Inspect the final diff and Git status. Do not commit, push, deploy, or
  modify the existing n8n workflow as part of this plan without separate
  authorization under the project's Git and security rules.
