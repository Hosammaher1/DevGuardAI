# Direct Codex, Antigravity, and Telegram task flow

Start an authorized task in Windows PowerShell with:

```powershell
& 'C:\Users\HP\AppData\Roaming\npm\codex.cmd' -C 'D:\programming\DevGuardAI' --sandbox workspace-write "Follow tooling/CODEX-ANTIGRAVITY-TELEGRAM.md exactly. Codex plans and reviews; Antigravity alone edits code. Task: <approved task description>"
```

Use this procedure when a user starts an **authorized** coding task in Codex CLI.
The current DevGuard application remains outside execution scope until its project
permissions and safety checks are explicitly satisfied. Setup and smoke tests use
only a disposable project.

1. Inspect `AGENTS.md`, task scope, Git status, and existing uncommitted files.
   Preserve every existing change. Stop before implementation if the requested
   edit overlaps an existing change or project execution is not authorized.
2. Create one task ID (a UUID hex string). Send `task_started` with the factual
   task description using `tooling/codex_agy_telegram.py`. Codex then writes a
   bounded plan in the conversation. Codex must not edit project code.
3. Before launching Antigravity, send `implementation_started` with the same ID.
   Launch the installed `agy.exe` from the authorized project directory with
   `--print`, `--output-format json`, `--json-schema`, `--mode accept-edits`, and
   `--sandbox`. Use the existing
   `D:\programming-tools\universal-agent-router\src\universal_agent_router\agent_result.schema.json`.
   Tell Antigravity to edit only approved files and not invoke shell commands.
   Never pass `--dangerously-skip-permissions`.
4. Codex reviews Antigravity's actual diff, checks for unrelated or overwritten
   user changes, and runs or inspects real verification results. Any test command
   needs effective sandboxing or explicit permission. A claimed test result or a
   successful CLI exit is not verification.
5. Send `completed` only after Codex has verified the changed files and at least
   one passing check. Include actual file paths and test results. Otherwise send
   `failed` with the observed error. If notification delivery fails, report that
   separately; never claim Telegram received it.

The notifier maps these stages to event names already accepted by the existing
n8n workflow. It uses that workflow's HTTPS URL and the Router's encrypted
current-user credential. It does not run Router, poll Telegram commands, or
create a database. Its event ID is stable for a task and stage, so retrying a
failed delivery does not create another Telegram message after a confirmed send.

Example notifier calls from the repository root (PowerShell):

```powershell
$Python = 'C:\Users\HP\AppData\Local\Programs\Python\Python313\python.exe'
$TaskId = [guid]::NewGuid().ToString('N')
& $Python tooling/codex_agy_telegram.py task_started --task-id $TaskId --project DevGuardAI --description 'Approved task description'
& $Python tooling/codex_agy_telegram.py implementation_started --task-id $TaskId --project DevGuardAI --description 'Approved task description'
# After actual diff review and tests:
& $Python tooling/codex_agy_telegram.py completed --task-id $TaskId --project DevGuardAI --description 'Approved task description' --summary 'What was verified' --file 'path/to/changed-file' --test 'check name=passed'
# On failure, use `failed --error 'Observed failure'` instead of `completed`.
```

The notifier's completion guard checks the evidence fields. Codex must also
inspect the real command output and diff before sending success.
