"""Send direct Codex/Antigravity task events through the existing n8n webhook.

This imports only the Router's DPAPI credential reader; it never starts its
service, opens its SQLite database, or reads Telegram commands.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from hashlib import sha256
from pathlib import Path
from urllib.parse import urlparse
from urllib.request import Request, urlopen


DEFAULT_ROUTER_CONFIG = Path("D:/programming-tools/universal-agent-router/config/router.local.json")
STAGES = {
    "task_started": ("planning_started", "codex"),
    "implementation_started": ("task_started", "antigravity"),
    "completed": ("task_completed", "codex"),
    "failed": ("task_failed", "antigravity"),
}


def build_payload(
    stage: str,
    task_id: str,
    description: str,
    project: str,
    *,
    summary: str = "",
    files: list[str] | None = None,
    tests: list[dict[str, str]] | None = None,
    error: str = "",
    agent: str | None = None,
) -> dict:
    if stage not in STAGES:
        raise ValueError("unsupported task stage")
    if not re.fullmatch(r"[A-Za-z0-9._:-]{1,80}", task_id):
        raise ValueError("task ID must be 1-80 safe characters")
    if not description.strip() or not project.strip():
        raise ValueError("task description and project are required")
    event, default_agent = STAGES[stage]
    actor = agent or default_agent
    if actor not in {"codex", "antigravity"}:
        raise ValueError("agent must be codex or antigravity")
    changed = files or []
    checked = tests or []
    if stage == "completed":
        if not summary.strip() or not checked or any(
            not isinstance(item, dict)
            or not item.get("name")
            or not re.search(r"\b(pass|passed|success|ok)\s*$", item.get("result", ""), re.I)
            or re.search(r"\b(not|fail|failed|error|pending|untested)\b", item.get("result", ""), re.I)
            for item in checked
        ):
            raise ValueError("completion requires a summary and passing verification evidence")
    if stage == "failed" and not error.strip():
        raise ValueError("failure requires an error")
    payload = {
        "event_id": sha256(f"direct-cli:{task_id}:{event}".encode()).hexdigest()[:32],
        "event": event,
        "task_id": task_id,
        "agent": actor,
        "project_name": project.strip(),
        "task_description": description.strip(),
        "direct_cli": True,
    }
    if stage == "implementation_started":
        payload["executor"] = "antigravity"
    if stage == "completed":
        payload.update({
            "work_completed": f"{description.strip()} — {summary.strip()}",
            "files_changed": changed,
            "tests_executed": checked,
            "project_progress": "Direct CLI task verified by Codex review",
            "remaining_blockers": [],
            "recommended_next_task": "Await the next requested task",
        })
    if stage == "failed":
        payload.update({"summary": error.strip(), "error": error.strip(),
                        "files_changed": changed, "tests_executed": checked})
    return payload


def post_event(url: str, key: str, payload: dict, *, opener=urlopen) -> str:
    if urlparse(url).scheme != "https" or not urlparse(url).netloc:
        raise RuntimeError("n8n event URL must use HTTPS")
    if not key or not all(33 <= ord(char) <= 126 for char in key):
        raise RuntimeError("n8n credential is not HTTP-header safe")
    request = Request(
        url,
        data=json.dumps(payload, separators=(",", ":")).encode("utf-8"),
        headers={"Content-Type": "application/json", "X-Agent-Key": key},
        method="POST",
    )
    with opener(request, timeout=15) as response:
        receipt = json.loads(response.read().decode("utf-8"))
    if (
        not isinstance(receipt, dict)
        or receipt.get("ok") is not True
        or receipt.get("status") != "delivered"
        or receipt.get("event_id") != payload["event_id"]
        or not str(receipt.get("telegram_message_id", ""))
    ):
        raise RuntimeError("n8n did not confirm Telegram delivery")
    return str(receipt["telegram_message_id"])


def load_connection(config_path: Path) -> tuple[str, str]:
    config_path = config_path.resolve(strict=True)
    config = json.loads(config_path.read_text(encoding="utf-8"))
    router_root = config_path.parent.parent
    sys.path.insert(0, str(router_root / "src"))
    from universal_agent_router.credentials import DpapiProtector, EncryptedFileCredentials

    credentials = EncryptedFileCredentials(router_root / "state" / "credentials", DpapiProtector())
    n8n = config["n8n"]
    return str(n8n["event_url"]), credentials.get(str(n8n["agent_key_env"]))


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("stage", choices=STAGES)
    parser.add_argument("--task-id", required=True)
    parser.add_argument("--description", required=True)
    parser.add_argument("--project", required=True)
    parser.add_argument("--summary", default="")
    parser.add_argument("--file", action="append", default=[])
    parser.add_argument("--test", action="append", default=[], metavar="NAME=RESULT")
    parser.add_argument("--error", default="")
    parser.add_argument("--agent", choices=("codex", "antigravity"))
    parser.add_argument("--router-config", type=Path, default=DEFAULT_ROUTER_CONFIG)
    args = parser.parse_args(argv)
    tests = []
    for entry in args.test:
        if "=" not in entry:
            parser.error("--test must be NAME=RESULT")
        name, result = entry.split("=", 1)
        tests.append({"name": name.strip(), "result": result.strip()})
    payload = build_payload(
        args.stage, args.task_id, args.description, args.project,
        summary=args.summary, files=args.file, tests=tests, error=args.error, agent=args.agent,
    )
    url, key = load_connection(args.router_config)
    message_id = post_event(url, key, payload)
    print(json.dumps({"event_id": payload["event_id"], "telegram_message_id": message_id,
                      "status": "delivered"}))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
