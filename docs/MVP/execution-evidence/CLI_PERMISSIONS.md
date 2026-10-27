# Antigravity 1.4.8 command-permission investigation

Installed `agy --help` confirms accept-edits/auto/plan, --sandbox, print mode and the bypass flag (NOT used). Help exposes no per-invocation allow-command flag. Default CLI settings have no explicit agentMode/toolPermission/permissions override.

Cause: accept-edits approves workspace file edits, not shell commands. Commands default to Ask; headless requests are soft-denied even with exit0/status SUCCESS. Inspect denied_actions, timeout markers and actual tool outcome. [Official headless documentation](https://antigravity.google/docs/cli/headless/).

Configured using the native `/permissions` TUI, **Project** scope for `/mnt/d/programming/DevGuardAI` only. No global allow rule, sandbox disable, wildcard shell/interpreter permission, network grant or unsandboxed grant was added. The allowlist contains anchored regex rules for exactly:

1. `git -C /mnt/d/programming/DevGuardAI status --short`
2. `/mnt/c/xampp/php/php.exe D:/programming/DevGuardAI/backend/vendor/phpunit/phpunit/phpunit --configuration D:/programming/DevGuardAI/backend/phpunit.xml D:/programming/DevGuardAI/backend/tests/Feature/(Artifacts/ScanArtifactIsolationTest|Identity/MvpSessionTest|Scans/ScanAdmissionSafetyTest).php` (literal path dots escaped in the actual regex).

All unmatched commands retain default Ask. No rm/git mutation/shell/python -c/php -r/npm install/provider or arbitrary test-suite permission was granted. Native Project UI validated and saved the rules; no undocumented project config path was invented. [Official permissions manager](https://antigravity.google/docs/cli/commands/permissions).

Verification: headless git-status probe reported completed exit0 without denial. Native streaming PHPUnit probe showed the allowed command executing, then exit127: `/mnt/c/xampp/php/php.exe: No such file or directory`. Its sandbox filesystem cannot access the installed Windows PHP runtime. The process was not retried outside Antigravity isolation. No test PASS is inferred from CLI status SUCCESS. The unmatched harmless `git -C /mnt/d/programming/DevGuardAI rev-parse --is-inside-work-tree` probe was soft-denied with denied_actions=[command], confirming no general Git/shell grant.

Disposition: keep Antigravity sandbox and narrow project grants. Antigravity owns coding; Codex runs necessary trusted local development checks separately using installed Windows runtimes and in-memory test configuration after each batch. No runtime filesystem/network boundary is widened merely to expose Windows tooling. Comprehensive independent acceptance/security/release remain deferred.

Batch repair exception: one additional Project-only anchored rule permits `mv -n --` from the batch-created 2026_03_03 artifact scan-binding migration to its exact 2026_10_27 filename. It grants no general move/delete/overwrite capability; only this non-overwriting source/destination pair. Needed because the first batch test run reported16tests/7schema errors from migration chronology. Antigravity owns the repair; Codex will rerun the batch checks.
