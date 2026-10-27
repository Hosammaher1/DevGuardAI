# MVP-03 bounded local implementation handoff

Owner authorization: latest conversation authorizes all ordinary local implementation and safe tests; no commits/push/provider calls. Codex retains review/tests, Antigravity alone edits application code. Preserve all dirty edits.

Ruling: MVP-01 remains PARTIAL for mandatory real-provider/privacy/retention/financial decisions. Those decisions do not prevent local session security work. MVP-02 baseline local tests now passed (73 authorization matrix + 62 selected backend + 3 Python, frontend build); PostgreSQL/provider proof remains blocked. This authorizes only the safe MVP-03 local slice, never full downstream acceptance.

Read the existing OAuth controller, routes, membership/Workspace models and GitHubOAuthTest.php using native ReadFile/search tools. Do not use shell commands during this handoff; Codex runs tests. No .env/credentials.

Phase RED: EDIT ONLY backend/tests/Feature/Identity/MvpSessionTest.php (new). Add meaningful request/DB tests for: unauthenticated GET /api/session is denied; authenticated GET /api/session returns minimal user and only active workspaces with current role, omits foreign/inactive memberships, no token fields; GET /api/dev/session never logs in or creates users; repeated OAuth login preserves an inactive or downgraded existing personal-workspace membership rather than resetting Owner/version. Use Socialite fake, RefreshDatabase and synthetic IDs. Existing role enum stores lowercase strings. Test no plaintext token fields and CSRF/session guard appropriate to framework. No network or scan endpoint.

STOP after test edits, report file and cases; Codex observes RED before production changes. Do not mark task implemented yet. Subsequent GREEN allowlist: backend/app/Http/Controllers/Auth/GitHubOAuthController.php; backend/app/Http/Controllers/Auth/SessionController.php (new); backend/routes/web.php; exact test file. Preserve unrelated route additions.
