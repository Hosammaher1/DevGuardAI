import io
import json
import unittest

from tooling.codex_agy_telegram import build_payload, post_event


class TelegramPayloadTests(unittest.TestCase):
    def test_direct_stages_use_existing_n8n_event_names(self):
        started = build_payload("task_started", "task_123", "Disposable check", "scratch")
        implementing = build_payload("implementation_started", "task_123", "Disposable check", "scratch")

        self.assertEqual(started["event"], "planning_started")
        self.assertEqual(started["agent"], "codex")
        self.assertEqual(implementing["event"], "task_started")
        self.assertEqual(implementing["agent"], "antigravity")
        self.assertEqual(started["task_description"], "Disposable check")
        self.assertNotEqual(started["event_id"], implementing["event_id"])

    def test_completion_requires_passing_evidence_and_includes_details(self):
        with self.assertRaises(ValueError):
            build_payload("completed", "task_123", "Create note", "scratch", summary="Created", tests=[])
        with self.assertRaises(ValueError):
            build_payload("completed", "task_123", "Create note", "scratch", summary="Created",
                          tests=[{"name": "readback", "result": "failed"}])

        payload = build_payload("completed", "task_123", "Create note", "scratch",
                                summary="Created and reviewed", files=["note.txt"],
                                tests=[{"name": "readback", "result": "passed"}])
        self.assertEqual(payload["event"], "task_completed")
        self.assertEqual(payload["files_changed"], ["note.txt"])
        self.assertEqual(payload["tests_executed"], [{"name": "readback", "result": "passed"}])
        self.assertIn("Create note", payload["work_completed"])

    def test_failed_event_preserves_error(self):
        payload = build_payload("failed", "task_123", "Create note", "scratch", error="readback mismatch")
        self.assertEqual(payload["event"], "task_failed")
        self.assertEqual(payload["error"], "readback mismatch")


class TelegramPostTests(unittest.TestCase):
    def test_post_requires_matching_telegram_receipt(self):
        payload = build_payload("task_started", "task_123", "Disposable check", "scratch")

        class Response:
            def __init__(self, body):
                self.body = body

            def __enter__(self):
                return self

            def __exit__(self, *_args):
                return None

            def read(self):
                return io.BytesIO(json.dumps(self.body).encode()).read()

        requests = []

        def opener(request, *, timeout):
            requests.append((request, timeout))
            return Response({"ok": True, "status": "delivered", "event_id": payload["event_id"],
                             "telegram_message_id": "123"})

        self.assertEqual(post_event("https://example.invalid/hook", "secret", payload, opener=opener), "123")
        self.assertEqual(requests[0][0].headers["X-agent-key"], "secret")
        self.assertEqual(json.loads(requests[0][0].data)["task_description"], "Disposable check")

        with self.assertRaises(RuntimeError):
            post_event("http://example.invalid/hook", "secret", payload, opener=opener)
