import uvicorn
from fastapi import FastAPI, HTTPException, Header, Depends
from fastapi.middleware.cors import CORSMiddleware
from typing import Optional
from config import settings
from schemas import (
    FailureAnalysisRequest,
    FailureAnalysisResponse,
    ServiceHealthResponse,
)
from gemini_analyzer import analyze_failure

app = FastAPI(
    title="DevGuard AI Agent Service",
    description="Automated QA diagnosis, root-cause analysis and fix planning engine.",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost:5173",
        "http://127.0.0.1:5173",
        "http://localhost:8000",
        "http://127.0.0.1:8000",
        "http://localhost:3000",
        "http://127.0.0.1:3000",
    ],
    allow_credentials=True,
    allow_methods=["GET", "POST", "OPTIONS"],
    allow_headers=["*"],
)

def verify_internal_auth(
    x_devguard_internal_key: Optional[str] = Header(None, alias="X-DevGuard-Internal-Key"),
    authorization: Optional[str] = Header(None, alias="Authorization"),
):
    """Enforce strict workload authentication on private internal API endpoints."""
    # Accept header token or Bearer token
    token = x_devguard_internal_key
    if not token and authorization and authorization.startswith("Bearer "):
        token = authorization[7:].strip()

    if not token:
        raise HTTPException(
            status_code=401,
            detail="Unauthorized: missing DevGuard internal service key",
        )

    expected = settings.internal_api_key
    if not expected:
        raise HTTPException(
            status_code=403,
            detail="Forbidden: DEVGUARD_INTERNAL_KEY is not configured in environment",
        )

    if token != expected:
        raise HTTPException(
            status_code=403,
            detail="Forbidden: invalid DevGuard internal service key",
        )

    return True

@app.get("/health", response_model=ServiceHealthResponse)
def health_check():
    return ServiceHealthResponse(
        status="ok",
        service="DevGuard AI Agent Service",
        version="1.0.0",
        gemini_configured=settings.has_gemini_key,
        model=settings.ai_model if settings.has_gemini_key else "devguard-local-heuristic",
    )

@app.post("/internal/review", response_model=FailureAnalysisResponse)
def handle_internal_review(
    req: FailureAnalysisRequest,
    authenticated: bool = Depends(verify_internal_auth),
):
    if not req.test_name or not req.error_message:
        raise HTTPException(status_code=400, detail="test_name and error_message are required")
    return analyze_failure(req)

@app.post("/api/analyze-failure", response_model=FailureAnalysisResponse)
def handle_failure_analysis(
    req: FailureAnalysisRequest,
    authenticated: bool = Depends(verify_internal_auth),
):
    if not req.test_name or not req.error_message:
        raise HTTPException(status_code=400, detail="test_name and error_message are required")
    return analyze_failure(req)

if __name__ == "__main__":
    uvicorn.run(app, host=settings.host, port=settings.port)
