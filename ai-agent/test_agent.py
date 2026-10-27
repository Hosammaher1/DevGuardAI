import unittest
from fastapi.testclient import TestClient
from main import app
from config import settings
from schemas import FailureAnalysisRequest
from gemini_analyzer import heuristic_fallback_analysis

class TestAIAgent(unittest.TestCase):
    def setUp(self):
        self.client = TestClient(app)

    def test_health_check(self):
        resp = self.client.get("/health")
        self.assertEqual(resp.status_code, 200)
        data = resp.json()
        self.assertEqual(data["status"], "ok")
        self.assertEqual(data["service"], "DevGuard AI Agent Service")
        self.assertIn("gemini_configured", data)

    def test_heuristic_analysis_selector_mismatch(self):
        req = FailureAnalysisRequest(
            test_name="Checkout Button Click",
            file_path="tests/checkout.spec.ts",
            error_message="locator.click: Target page, context or browser has been closed. waiting for locator('#checkout-btn')",
            stack_trace="at CheckoutPage.submit (checkout.spec.ts:42:15)",
        )
        res = heuristic_fallback_analysis(req, "test-reason")
        self.assertEqual(res.failure_category, "SELECTOR_MISMATCH")
        self.assertIn("locator", res.root_cause.lower())
        self.assertTrue(res.is_mock)
        self.assertTrue(res.requires_approval)

    def test_heuristic_analysis_timeout(self):
        req = FailureAnalysisRequest(
            test_name="Load Dashboard",
            file_path="tests/dashboard.spec.ts",
            error_message="Timeout 30000ms exceeded while waiting for element '.metric-card'",
        )
        res = heuristic_fallback_analysis(req, "test-reason")
        self.assertEqual(res.failure_category, "TIMEOUT")
        self.assertTrue(res.is_mock)
        self.assertIsNotNone(res.code_patch)

    def test_analyze_failure_api_endpoint(self):
        settings.internal_api_key = "devguard-test-secret"
        payload = {
            "test_name": "Login Submit",
            "file_path": "tests/auth.spec.ts",
            "error_message": "expect(received).toBe(expected) // Expected: 'Welcome' // Received: 'Error'",
            "stack_trace": "Error: expect().toBe() failed\n  at auth.spec.ts:25:10"
        }
        resp = self.client.post(
            "/api/analyze-failure",
            json=payload,
            headers={"X-DevGuard-Internal-Key": "devguard-test-secret"},
        )
        self.assertEqual(resp.status_code, 200)
        data = resp.json()
        self.assertEqual(data["failure_category"], "ASSERTION_FAILURE")
        self.assertTrue(data["requires_approval"])
        self.assertIn("root_cause", data)
        self.assertIn("suggested_fix", data)

    def test_internal_review_endpoint_enforces_auth(self):
        settings.internal_api_key = "devguard-test-secret"
        payload = {
            "test_name": "Login Submit",
            "file_path": "tests/auth.spec.ts",
            "error_message": "expect(received).toBe(expected)",
        }
        # Without header => 401 Unauthorized
        resp = self.client.post("/internal/review", json=payload)
        self.assertEqual(resp.status_code, 401)
        self.assertIn("missing", resp.json()["detail"].lower())

        # With invalid header => 403 Forbidden
        resp_bad = self.client.post(
            "/internal/review",
            json=payload,
            headers={"X-DevGuard-Internal-Key": "wrong-key"}
        )
        self.assertEqual(resp_bad.status_code, 403)
        self.assertIn("invalid", resp_bad.json()["detail"].lower())

        # With unconfigured server key => 403 Forbidden
        settings.internal_api_key = ""
        resp_unconfigured = self.client.post(
            "/internal/review",
            json=payload,
            headers={"X-DevGuard-Internal-Key": "any-key"}
        )
        self.assertEqual(resp_unconfigured.status_code, 403)
        self.assertIn("not configured", resp_unconfigured.json()["detail"].lower())

        # Restore key: With valid header => 200 OK
        settings.internal_api_key = "devguard-test-secret"
        resp_ok = self.client.post(
            "/internal/review",
            json=payload,
            headers={"X-DevGuard-Internal-Key": "devguard-test-secret"}
        )
        self.assertEqual(resp_ok.status_code, 200)
        data = resp_ok.json()
        self.assertIn("failure_category", data)
        self.assertTrue(data["requires_approval"])

        # With Authorization Bearer token => 200 OK
        resp_bearer = self.client.post(
            "/internal/review",
            json=payload,
            headers={"Authorization": "Bearer devguard-test-secret"}
        )
        self.assertEqual(resp_bearer.status_code, 200)

if __name__ == "__main__":
    unittest.main()
