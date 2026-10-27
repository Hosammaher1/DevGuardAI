import os
from pathlib import Path
from pydantic import BaseModel

def _load_env_file():
    """Load .env from project root or backend directory if present."""
    root_dir = Path(__file__).resolve().parent.parent
    for env_path in [root_dir / ".env", root_dir / "backend" / ".env", Path(".env")]:
        if env_path.exists():
            try:
                with open(env_path, "r", encoding="utf-8") as f:
                    for line in f:
                        line = line.strip()
                        if line and not line.startswith("#") and "=" in line:
                            k, v = line.split("=", 1)
                            k = k.strip()
                            v = v.strip().strip('"').strip("'")
                            if k not in os.environ:
                                os.environ[k] = v
            except Exception:
                pass

_load_env_file()

class Settings(BaseModel):
    host: str = os.getenv("AI_SERVICE_HOST", "127.0.0.1")
    port: int = int(os.getenv("AI_SERVICE_PORT", "8001"))
    gemini_api_key: str = os.getenv("GEMINI_API_KEY") or os.getenv("GOOGLE_API_KEY", "")
    ai_model: str = os.getenv("DEVGUARD_AI_MODEL", "gemini-2.5-flash")
    internal_api_key: str = os.getenv("DEVGUARD_INTERNAL_KEY", "")
    trusted_project_path: str = str(Path(__file__).resolve().parent.parent / "trusted-demo")

    @property
    def has_gemini_key(self) -> bool:
        return bool(self.gemini_api_key and len(self.gemini_api_key.strip()) > 10)

settings = Settings()
