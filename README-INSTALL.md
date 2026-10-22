# DevGuard Codex Starter

This package is **NOT installed in your Codex account**; it contains files you can copy to your actual project.

## Installation (Windows / PowerShell)

After creating/opening your actual DevGuard project directory in Codex, extract this archive into the project root. It adds:
- `.agents/skills/devguard-acceptance-specs/SKILL.md`
- `AGENTS.md`
- `docs/superpowers/plans/00-acceptance-first-plan.md`
- `docs/superpowers/tests/README.md`

Codex can discover skills in `.agents/skills` relative to the project in supported environments. Alternatively copy the `devguard-acceptance-specs` folder into `~/.agents/skills/` for your user-wide setup, and verify skill discovery in the Codex app/CLI.

**Superpowers:** These skills are already present in this ChatGPT environment, not necessarily in your separate Codex installation. Verify their presence in Codex independently; do not copy their source from this archive, as this archive doesn't contain them.

## Verification
1. Confirm `SKILL.md` exists and its YAML frontmatter is well formed.
2. Restart/reload Codex session or skill discovery.
3. Ask Codex to list or select `devguard-acceptance-specs`.
4. Perform independent baseline/with-skill behavioral tests using `superpowers:writing-skills` before treating custom skill as validated.

No tests or code were run on DevGuard itself.
