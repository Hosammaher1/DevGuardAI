from typing import List, Optional
from pydantic import BaseModel, Field

class FailureAnalysisRequest(BaseModel):
    test_name: str = Field(..., description="Name of the failed test")
    file_path: str = Field(..., description="Path to the test file")
    error_message: str = Field(..., description="Direct error or failure assertion message")
    stack_trace: Optional[str] = Field(None, description="Full or partial stack trace")
    console_logs: Optional[List[str]] = Field(default_factory=list, description="Browser or execution console logs")
    dom_snapshot: Optional[str] = Field(None, description="Snippet of relevant DOM around the failure")
    code_context: Optional[str] = Field(None, description="Source code of the test or tested component")
    screenshot_base64: Optional[str] = Field(None, description="Base64 encoded screenshot image if available")

class FailureAnalysisResponse(BaseModel):
    root_cause: str = Field(..., description="Human-understandable root cause explanation")
    failure_category: str = Field(..., description="Categorization: SELECTOR_MISMATCH, TIMEOUT, ASSERTION_FAILURE, etc.")
    suggested_fix: str = Field(..., description="Step-by-step or narrative suggested fix")
    code_patch: Optional[str] = Field(None, description="Suggested unified diff or code replacement")
    confidence_score: float = Field(..., ge=0.0, le=1.0, description="Confidence in the diagnosis")
    model_used: str = Field(..., description="Model identifier used for analysis")
    is_mock: bool = Field(False, description="Whether the response came from a fallback heuristic instead of live Gemini")
    requires_approval: bool = Field(True, description="Strict safety boundary: fixes require human review")

class ServiceHealthResponse(BaseModel):
    status: str
    service: str
    version: str
    gemini_configured: bool
    model: str
