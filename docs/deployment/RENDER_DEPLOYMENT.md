# DevGuard AI — Render Free Deployment Guide

**Target Service Name:** `devguard-api`  
**Root Directory:** `backend`  
**Environment:** `Docker`  
**Date:** 2026-10-28  

---

## 1. Architecture Overview

The DevGuard AI Laravel backend is packaged using an Alpine-based multi-process Docker container combining:
- **PHP 8.2 FPM** (`php:8.2-fpm-alpine`) with required PostgreSQL, BCMath, OpCache, Zip, and PCNTL extensions.
- **Nginx** acting as the reverse proxy listening on `0.0.0.0:${PORT}` (where `$PORT` is injected dynamically by Render).
- **StdErr / StdOut Logging** routing all Nginx and Laravel error/access logs directly to Render's live dashboard.
- **Zero Local Secrets:** No `.env` files, SSH keys, or API tokens are baked into the Docker image; configuration is strictly environment-variable driven.
- **Production Guard:** If `APP_ENV=production` and `DB_CONNECTION=sqlite`, the container fails closed at startup.

---

## 2. Render Service Configuration

When creating the Web Service on Render:

| Setting | Value |
|---|---|
| **Service Name** | `devguard-api` |
| **Region** | Oregon (US West) or Frankfurt (EU Central) |
| **Branch** | `dev` (or selected deployment branch) |
| **Root Directory** | `backend` |
| **Runtime** | `Docker` |
| **Dockerfile Path** | `./Dockerfile` |
| **Instance Type** | Free (0.1 CPU, 512 MB RAM) |
| **Health Check Path** | `/health` (or `/up`) |
| **Auto-Deploy** | Yes (recommended on verified branch updates) |

---

## 3. Required Render Environment Variables

Configure the following variables in the Render Dashboard under **Environment**:

### Application Core
| Variable | Value | Description |
|---|---|---|
| `APP_NAME` | `DevGuard AI` | Application name |
| `APP_ENV` | `production` | Production environment flag |
| `APP_DEBUG` | `false` | Disables debug mode and sensitive stack traces |
| `APP_KEY` | `base64:...` | 32-byte AES key generated via `php artisan key:generate --show` |
| `APP_URL` | `https://devguard-api.onrender.com` | Public URL of the Render service |
| `LOG_CHANNEL` | `stderr` | Routes application logs to container stderr for Render visibility |

### Database (PostgreSQL)
Render Free PostgreSQL provides a connection string via `DATABASE_URL`. The backend automatically maps `DATABASE_URL` to the `pgsql` database connection:

| Variable | Value | Description |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | Forces PostgreSQL driver |
| `DATABASE_URL` | `postgres://user:pass@host:5432/dbname` | Injected automatically when linking Render Postgres |
| `DB_SSLMODE` | `require` | Enforces TLS for cloud PostgreSQL connections |

### Security & Internal Keys
| Variable | Value | Description |
|---|---|---|
| `DEVGUARD_INTERNAL_KEY` | *(Secret random string)* | Shared internal key for workload authentication with AI Agent service |
| `DEVGUARD_ARTIFACT_LINK_TTL_SECONDS` | `300` | Signed artifact link expiry (5 minutes) |
| `DEVGUARD_REPORT_RETENTION_DAYS` | `30` | Default report retention period |

### GitHub App Integration
| Variable | Value | Description |
|---|---|---|
| `GITHUB_APP_ID` | *(Your GitHub App ID)* | GitHub App numerical ID |
| `GITHUB_PRIVATE_KEY` | `-----BEGIN RSA PRIVATE KEY-----...` | Multi-line PEM private key generated in GitHub App settings |
| `GITHUB_WEBHOOK_SECRET` | *(Secret random string)* | Shared secret for verifying GitHub webhook signatures |
| `GITHUB_CLIENT_ID` | *(OAuth Client ID)* | GitHub OAuth Client ID |
| `GITHUB_CLIENT_SECRET` | *(OAuth Client Secret)* | GitHub OAuth Client Secret |
| `GITHUB_REDIRECT_URI` | `https://devguard-api.onrender.com/auth/github/callback` | OAuth redirect URL |

### External AI Service
| Variable | Value | Description |
|---|---|---|
| `DEVGUARD_AI_SERVICE_URL` | `https://devguard-ai-agent.onrender.com` | URL of the Python AI Agent service |

---

## 4. Database Migrations (Release / Pre-Deploy Command)

**Never run migrations during Docker build.** Run migrations as Render's **Pre-Deploy Command** (available under service settings):

```sh
php artisan migrate --force
```

If using Render's manual deployment without pre-deploy hooks, run via Render Shell:
```sh
php artisan migrate --force
```

---

## 5. Critical Incompatibilities & Limitations on Render Free Tier

> [!WARNING]
> The DevGuard AI MVP cannot achieve true production readiness on Render Free without addressing the following architectural constraints:

### A. Scan Execution & Runner Isolation
- **The Issue:** DevGuard executes Playwright (Chromium headless browser) for E2E tests, which requires substantial memory (often 400MB–1GB+) and system libraries.
- **The Free Tier Limit:** Render Free allocates only **512 MB RAM** and **0.1 CPU**. Launching Chromium inside this container will trigger instantaneous Out-Of-Memory (`OOMKilled`) termination.
- **Isolation Constraint:** Docker-in-Docker or nested virtualization is disallowed on Render. Untrusted repository test code must never run on the control plane host.
- **Remediation:** Scan execution must be delegated to an external isolated worker (e.g. AWS ECS/Fargate, Fly.io Machines, or dedicated worker VM) communicating via an internal dispatch queue.

### B. Ephemeral Disk & Artifact Storage
- **The Issue:** Render Free uses ephemeral storage. Any file written to `/var/www/html/storage/app` is permanently lost when the service restarts, redeploys, or spins down after idle.
- **Remediation:** In production, artifact storage must be bound to an S3-compatible bucket (AWS S3, Cloudflare R2, or Supabase Storage) by setting `FILESYSTEM_DISK=s3`.

### C. Free Tier Sleep / Spin-Down & Webhook Timeouts
- **The Issue:** Render Free web services spin down after 15 minutes of inactivity. Cold-start wake-up takes 30–50 seconds.
- **The Risk:** GitHub webhook delivery times out after **10 seconds**. If a GitHub App event arrives while `devguard-api` is asleep, GitHub marks the delivery as failed (`504 Gateway Timeout`).
- **Remediation:** Configure a lightweight external uptime monitor (e.g. UptimeRobot, Better Uptime) to ping `https://devguard-api.onrender.com/health` every 10 minutes to maintain active state.

### D. Single-Process Queue Constraints
- **The Issue:** Render Free web services permit only one process (Nginx + PHP-FPM). Background queue workers (`php artisan queue:work`) cannot run as a separate free process on the same service.
- **Remediation:** For staging on Free tier, use `QUEUE_CONNECTION=sync` for short synchronous operations, or deploy a dedicated Background Worker on paid tier for asynchronous scan/fix pipelines.

### E. 30-Day PostgreSQL Expiry
- **The Issue:** Render Free PostgreSQL databases are deleted 30 days after creation.
- **Remediation:** Use a persistent database (Render Paid Postgres, Supabase, Neon, or AWS RDS) for any non-ephemeral deployment.

---

## 6. Readiness Verdict

| Dimension | Status | Notes |
|---|:---:|---|
| **Docker Configuration** | **READY** | Validated Alpine runtime, Nginx + PHP-FPM, dynamic `$PORT`, health endpoints |
| **Source & Secret Safety** | **READY** | `.dockerignore` excludes `.env`, secrets, test files, and local caches |
| **PostgreSQL Support** | **READY** | `pdo_pgsql` installed, `DATABASE_URL` mapping supported |
| **Logging & Monitoring** | **READY** | Nginx & PHP-FPM routed to stdout/stderr |
| **Render Free Feasibility** | **STAGING ONLY** | Suitable for UI/API demonstration; blocked for live scans by 512MB RAM & ephemeral disk |
