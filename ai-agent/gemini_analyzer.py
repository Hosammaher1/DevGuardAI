import json
import logging
import re
from typing import Optional
from config import settings
from schemas import FailureAnalysisRequest, FailureAnalysisResponse

logger = logging.getLogger("devguard.ai_analyzer")

SYSTEM_INSTRUCTION = """You are DevGuard AI's senior automated QA diagnostic engine.
Your task is to analyze Playwright end-to-end browser test failures, identify the precise root cause, categorize the failure, and provide an actionable, minimal code fix.

Respond ONLY with a valid JSON object with the following fields:
{
  "root_cause": "Detailed explanation of why the test failed",
  "failure_category": "One of: SELECTOR_MISMATCH, TIMEOUT, ASSERTION_FAILURE, NETWORK_ERROR, RUNTIME_EXCEPTION, RACE_CONDITION, FLAKY_TEST",
  "suggested_fix": "Clear explanation of how to resolve the failure in the test code or application",
  "code_patch": "Code snippet or unified diff showing the exact fix",
  "confidence_score": 0.95
}
"""

def heuristic_fallback_analysis(req: FailureAnalysisRequest, reason: str) -> FailureAnalysisResponse:
    """Deterministic local diagnostic when Gemini API is unavailable or unconfigured."""
    err = (req.error_message or "").lower()
    trace = (req.stack_trace or "").lower()
    
    category = "UNKNOWN_FAILURE"
    root_cause = "Test execution terminated with an error."
    suggested_fix = "Review the error trace and verify application state."
    patch = None
    confidence = 0.70

    if "timeout" in err or "timed out" in err or "waitfor" in trace:
        category = "TIMEOUT"
        root_cause = f"Element or page condition did not resolve within timeout: {req.error_message}"
        suggested_fix = "Ensure the target element is loaded, or increase action timeout using { timeout: 10000 }."
        patch = "// Ensure element is attached before action\nawait page.locator(selector).waitFor({ state: 'visible', timeout: 10000 });"
        confidence = 0.85
    elif "strict mode violation" in err or "resolved to" in err and "elements" in err:
        category = "SELECTOR_MISMATCH"
        root_cause = "Strict mode violation: locator resolved to multiple DOM elements."
        suggested_fix = "Narrow down the selector using .first(), specific role, or text filter."
        patch = "await page.getByRole('button', { name: 'Submit' }).first().click();"
        confidence = 0.92
    elif "locator." in err or "waiting for locator" in err or "element not found" in err:
        category = "SELECTOR_MISMATCH"
        root_cause = f"Playwright locator could not find the target element on the page: {req.error_message}"
        suggested_fix = "Verify the DOM attributes and selector. Inspect whether the element ID, class, or text changed in the latest UI."
        patch = "// Replace brittle selector with robust accessible locator\nawait page.getByTestId('target-button').click();"
        confidence = 0.88
    elif "expect(" in err or "received:" in err or "expected:" in err:
        category = "ASSERTION_FAILURE"
        root_cause = f"Assertion mismatch between expected and actual values: {req.error_message}"
        suggested_fix = "Check if test expectation is outdated or if backend returned unexpected payload."
        patch = "// Update expected assertion or fix upstream data\nawait expect(page.locator('.status')).toHaveText('Success');"
        confidence = 0.86
    elif "net::err" in err or "500" in err or "failed to load resource" in err:
        category = "NETWORK_ERROR"
        root_cause = f"Network or API endpoint error during browser test: {req.error_message}"
        suggested_fix = "Check backend API health, verify endpoint URL, and check network tab in dev tools."
        confidence = 0.82

    return FailureAnalysisResponse(
        root_cause=f"[Local Diagnostic] {root_cause}",
        failure_category=category,
        suggested_fix=f"{suggested_fix} (Note: {reason})",
        code_patch=patch,
        confidence_score=confidence,
        model_used="devguard-local-heuristic",
        is_mock=True,
        requires_approval=True
    )

def analyze_failure(req: FailureAnalysisRequest) -> FailureAnalysisResponse:
    """Analyze Playwright test failure using Gemini Developer API or local fallback."""
    if not settings.has_gemini_key:
        return heuristic_fallback_analysis(req, "No GEMINI_API_KEY configured - set GEMINI_API_KEY in .env for live Gemini analysis")

    try:
        from google import genai
        client = genai.Client(api_key=settings.gemini_api_key)

        prompt = f"""Analyze this Playwright test failure:
Test Name: {req.test_name}
File Path: {req.file_path}
Error Message: {req.error_message}

Stack Trace:
{req.stack_trace or 'N/A'}

Browser Console Logs:
{json.dumps(req.console_logs, indent=2) if req.console_logs else 'None'}

DOM Snapshot:
{req.dom_snapshot or 'None'}

Code Context:
{req.code_context or 'None'}
"""
        response = client.models.generate_content(
            model=settings.ai_model,
            contents=[SYSTEM_INSTRUCTION, prompt],
        )

        text = response.text.strip()
        # Extract JSON from code block if wrapped
        if "```json" in text:
            text = text.split("```json", 1)[1].split("```", 1)[0].strip()
        elif "```" in text:
            text = text.split("```", 1)[1].split("```", 1)[0].strip()

        data = json.loads(text)
        return FailureAnalysisResponse(
            root_cause=data.get("root_cause", "Root cause identified by Gemini"),
            failure_category=data.get("failure_category", "PLAYWRIGHT_ERROR"),
            suggested_fix=data.get("suggested_fix", "Suggested fix provided by Gemini"),
            code_patch=data.get("code_patch"),
            confidence_score=float(data.get("confidence_score", 0.90)),
            model_used=settings.ai_model,
            is_mock=False,
            requires_approval=True
        )
    except Exception as exc:
        logger.warning("Gemini API call failed, falling back to local heuristic: %s", exc)
        return heuristic_fallback_analysis(req, f"Gemini API returned error: {str(exc)[:120]}")
