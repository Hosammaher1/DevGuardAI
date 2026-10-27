# Execution baseline — 2026-10-27

- Owner explicitly authorized all 32 local MVP tasks; final independent security/E2E acceptance and release remain deferred.
- Current feature branch preserved in place with 202-file baseline hash manifest. No history changes.
- Linux PHP unavailable; installed Windows PHP 8.2.12 works through explicitly approved outer-sandbox escalation.
- `php.exe vendor/phpunit/phpunit/phpunit --configuration phpunit.xml tests/Unit/Domain/Identity/AuthorizationMatrixTest.php` in backend: exit 0, 73 tests, 146 assertions.
- `php.exe vendor/phpunit/phpunit/phpunit --configuration phpunit.xml tests/Feature/Identity tests/Feature/Artifacts tests/Feature/Credits tests/Feature/OperatingMode tests/Unit/Domain/Runners`: exit 0, 62 tests, 675 assertions; in-memory SQLite. Scan demo and PostgreSQL DatabaseTruncation suites excluded.
- Linux `npm run build`: exit 1, existing dependencies lack @rollup/rollup-linux-x64-gnu. No dependencies deleted or reinstalled.
- Existing Windows Node running `node_modules/vite/bin/vite.js build` in frontend: exit 0, Vite 5.4.21, 31 modules. This verifies the current demo UI compiles, not product functionality.
- Linux Python 3.14.4 lacks fastapi/pydantic/httpx/pytest. Existing Windows Python 3.13 provides FastAPI/Pydantic/httpx. Three selected unittest checks (health, selector heuristic, timeout heuristic) passed, exit 0, 0.011s; provider-capable API test excluded. Heuristic checks certify prototype behavior only.
- Headless Antigravity accept-edits with sandbox: first launch failed outer-sandbox logs/listener; approved escalated launch exited 0 with command permission auto-denied and no implementation. Native interactive fallback started; reviewing individual permission requests, no bypass flags.
- No customer code/targets, provider calls, PostgreSQL truncation, secrets inspection, commits or pushes.

## Delegation retry evidence

Native interactive command execution was interrupted after stalling; session exited normally (0), conversation c82155c4-a941-45fd-b063-bc1c3a0ac171. File-only headless MVP-03 RED attempt then timed out after 120 seconds with turn in progress, exit 0 and no test file or application change. Codex did not resend automatically while a process was active. A final smaller 90-second streaming native-file-only diagnostic is bounded to a single new test.

Streaming diagnostic conversation `c0ce0b24-2e0a-4589-993b-745eae63d478`: native reads worked; one requested nonexistent ArtifactTest.php returned TOOL_ERROR (no source change), then further reads completed. At 90s CLI returned partial timeout and a misleading result status SUCCESS with empty response. This is NOT accepted success. Resumed that exact conversation once with lower effort/180s and no further reads, to write only the RED test.

Native-only resumed Antigravity with low effort successfully wrote `backend/tests/Feature/Identity/MvpSessionTest.php`; no command tools. Codex ran it against in-memory SQLite: exit 1, one failure, expected401 vs actual404. This is meaningful RED for missing /api/session. The native file-edit path is therefore VERIFIED; prior timeouts do not block further bounded local tasks. Remaining session-security RED cases delegated before production changes.

Antigravity extended MVP-03 RED tests with active-membership session projection, demo-login denial and revoked-membership preservation. Codex review found duplicate personal_owner_user_id in the inactive-workspace fixture; returned that test-only correction before the RED run so schema errors are not misreported as behavioral failure.

MVP-03 four-case RED verified: exit1, four tests/nine assertions/four behavioral failures: missing session endpoint401/200 vs404; demo auto-login200 vs404; OAuth reactivated inactive membership. Antigravity GREEN scoped to routes, OAuth controller and new SessionController; tests frozen.

MVP-03 GREEN: exact session/OAuth selector completed exit0, 9 tests/54 assertions. Changes: new SessionController, OAuth new-workspace-only membership creation, removal of dev auto-login and auth-protected session route. Broader identity/artifact/matrix regression underway. No real GitHub call; final acceptance remains deferred.

Additional safe existing-module regression: GitHub RepositoryConnectionTest + Credits + OperatingMode + runner unit contract: exit0,41tests562assertions. This is synthetic/local validation only; it does not establish server-correlated GitHub installation authority or real isolation.
MVP-08 three tests created. Codex review caught IssuedArtifactLink.token versus nonexistent rawToken; returned test-only correction before execution.

MVP-08 RED:3tests4assertions,3failures exit1. Mixed tenant route returned200 rather than404; missing artifact returned shared image200 rather than404; own encrypted PNG was not delivered (binary file fallback returned false content). This reproduces the reviewed parent-scope and shared-image defects using only synthetic metadata and no scan execution.
