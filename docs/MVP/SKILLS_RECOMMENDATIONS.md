# Installed skills and recommendations

Inspection: 2026-10-27. No skills/tools/plugins were installed or authored by this planning work. Files on disk, session-discoverable skills, documented discovery paths and proposed skills are different states. A cached or built-in file does not prove an authenticated agent activated it.

## Codex evidence

Active terminal executable: `/usr/local/bin/codex`, `codex-cli 0.164.0`. Help confirms noninteractive `exec` and `review`, sandbox/approval controls and plugins. This conversation's supplied catalog is the evidence for enabled session skills; filesystem enumeration independently confirms paths below. `review-agent` exists on disk but was not in the supplied skill catalog: do not assume direct invocation availability.

| Layer | Actual inspection | Status |
|---|---|---|
| System | `/home/hp/.codex/skills/.system/{imagegen,openai-docs,skill-creator,skill-installer,review-agent}/SKILL.md` | Present; first four appear in session catalog, review-agent disk-only |
| Global conventional user skills | `/home/hp/.agents/skills` not found in inspected environment | No custom skills established at this path |
| Project | `/mnt/d/programming/DevGuardAI/.agents/skills/devguard-acceptance-specs/SKILL.md` | Present and session-discoverable; DRAFT, structural checks only; pressure test/human review still required |
| Plugin methodology | `/home/hp/.codex/plugins/cache/openai-curated-remote/superpowers/7.0.1/skills/` | Present and session-discoverable; exact names below |
| Other active plugins | plugin-management 0.1.0, Sites 1.1.2, work-pets 0.1.6 paths under same cache | Session-discoverable; unrelated to application implementation needs |
| Template cache | openai-templates 0.1.1 contains system-design/project-tracker/kickoff and other templates | Files present; not advertised as enabled skills in this session; not automatically active |
| Windows side | `/mnt/c/Users/HP/.codex/skills/.system/` contains corresponding five system skills | Separate OS installation files; not the active Linux discovery root |
| Project `.codex` and administrator root | No additional SKILL.md found in inspected project `.codex` or `/etc/codex/skills` | No claim of an exhaustive scan of every machine directory |

Exact methodology names observed: `brainstorming`, `diagnosing-superpowers`, `dispatching-parallel-agents`, `executing-plans`, `finishing-a-development-branch`, `receiving-code-review`, `requesting-code-review`, `subagent-driven-development`, `systematic-debugging`, `test-driven-development`, `using-git-worktrees`, `using-superpowers`, `verification-before-completion`, `writing-plans`, `writing-skills` (15). Presence does not mean each skill was used. Relevant planning instructions read: using-superpowers, brainstorming, writing-plans, verification-before-completion, openai-docs and the draft acceptance skill. Implementation/TDD and skill creation were not performed.

Official [Codex customization guidance](https://learn.chatgpt.com/docs/customization/overview) documents user `~/.agents/skills` and project `.agents/skills`; [skill guidance](https://learn.chatgpt.com/docs/build-skills) describes name/description discovery followed by loading selected SKILL.md content. The supplied system/plugin paths demonstrate additional environment-provided surfaces here. Do not infer that merely copying another application's cache globally makes it discoverable or enabled.

## Antigravity evidence and path differences

Active executable `/home/hp/.local/bin/agy`, version 1.4.8. It is a native Linux binary. The Windows npm `gemini` command is separate; do not conflate the products or their skill roots. `agy --help` confirms print mode, execution modes and sandbox flags, but has no standalone skills-list subcommand in the observed help.

[Official Antigravity skills documentation](https://antigravity.google/docs/skills/) documents workspace `.agents/skills/<name>/`, CLI global `~/.gemini/antigravity-cli/skills/<name>/` and plugin `~/.gemini/antigravity-cli/plugins/<name>/skills/`; it notes compatibility with `.agent/skills`. App, CLI and IDE roots differ. The shared project `.agents/skills` is the documented overlap with Codex; shared content still needs cross-agent validation.

Actual built-in files were found under both `/home/hp/.gemini/antigravity-cli/builtin/skills/` and `/mnt/c/Users/HP/.gemini/antigravity-cli/builtin/skills/`; the Windows App also has `/mnt/c/Users/HP/.gemini/antigravity/builtin/skills/`. Nine built-ins were observed: `agy-customizations`, `antigravity_guide`, `automation`, `generative_ui`, `migrate-workflows`, `permissioned-github`, `plugin`, `run-workflow`, `ui-plugin-navigation`. These are disk-present built-ins, not verified as mounted by the active authenticated CLI session.

The actual bundled `agy-customizations/SKILL.md` was read on Linux and Windows. It describes workspace aliases `.agents`, `.agent`, `_agents`, `_agent`, global customization root `~/.gemini/config/`, declared configurations and built-ins mounted by name. This is broader than the public CLI path table. Treat the difference as version/surface-sensitive: prefer documented CLI skill roots and confirm a harmless explicit skill discovery with the authenticated installed version before relying on global/shared loading. Do not create symlinks, JSON config or new skills in this session.

No custom SKILL.md was found in the inspected Linux global customization locations or Windows `~/.gemini/config`; conventional Windows CLI global skills/plugins directories were not found in the initial path check. The only project custom skill found is the draft acceptance skill in `.agents/skills`. The corrected no-tool model-response probe passed, but actual skill activation was not tested: installed-file evidence does not establish runtime discovery success.

## Recommendations by responsibility

| Need | Preferred available guidance | Missing capability / recommendation | Assigned agent |
|---|---|---|---|
| Architecture | brainstorming + writing-plans; original Engineering Blueprint and MVP architecture | Optional proposed devguard architecture review guide only if recurrent gaps justify it; no generic architecture skill presumed installed | Codex |
| MVP planning | writing-plans, draft acceptance skill, canonical PD/SG policies | Validate acceptance skill first; keep this task ledger as source rather than introduce a task platform | Codex |
| Task management/direct delegation | bounded task handoff and verification-before-completion; executing-plans principles | No orchestration plugin needed; do not use subagent-driven implementation in place of named Antigravity engineer | Codex manager; Antigravity one-task worker |
| Laravel | Existing identity/artifact patterns, official Laravel docs; TDD after authorization | No installed Laravel specialist. Proposed devguard-laravel-control-plane for policy/session/queue/transactions | Antigravity implements; Codex boundary review |
| React JavaScript | Existing app, official React/Vite docs; TDD/verification | No installed React specialist. Proposed devguard-react-ux must preserve JS and actual-state/accessibility rules | Antigravity; Codex UX/authority review |
| FastAPI/Python | Existing prototype, official FastAPI/Pydantic docs; debugging/TDD | No installed FastAPI specialist. Proposed scoped internal-service guidance for auth/schema/budget/injection | Antigravity; Codex contracts/security |
| Playwright | Approved trusted recipes and official Playwright docs | External OpenAI playwright skill candidate below; no installed browser-testing skill established | Antigravity authorized tests; Codex evidence review |
| Security audit | Exact SG-01–12 plus draft acceptance skill and code review methodology | External security guidance candidates require PHP/Laravel-specific supplementation and independent assurance | Codex; independent review when approved |
| Automated QA | TDD, systematic-debugging, verification-before-completion; 293-case catalog as reference | No specialized installed benchmark/security-runner QA skill; proposed runner/evidence guidance before complex integration | Antigravity executes authorized checks; Codex accepts |
| Code review | requesting-code-review and receiving-code-review; installed `codex review` command | review-agent is disk-only; verify availability before promising it; direct Codex review remains mandatory | Codex |

## External candidates, not installed or approved

- [OpenAI curated playwright](https://github.com/openai/skills/tree/main/skills/.curated/playwright): source location inspected as a candidate for browser workflows. Review its actual current SKILL.md, dependency/tool side effects, headless setup and sandbox assumptions before installation; it does not provide product execution isolation by itself.
- [OpenAI security-best-practices](https://github.com/openai/skills/tree/main/skills/.curated/security-best-practices): source location inspected as a security guidance candidate. Confirm supported languages/frameworks and PHP gaps; generic advice cannot replace tenant, ledger, provider and SG testing. Not a completed quality or compatibility audit.
- [Laravel Boost](https://github.com/laravel/boost): official Laravel-focused developer integration, not an installed standalone Laravel skill here. It involves an MCP/tool installation and therefore is deferred under the explicit no-new-tools/MCP constraint; it is not required for this workflow.
- React and FastAPI: prefer direct official framework documentation plus narrow reviewed project guidance over an unvetted “full-stack” skill pack. No third-party React/FastAPI skill is represented as audited or installed.

These recommendations are candidates with provenance, not blanket endorsements or proof of compatibility. No installation is needed to review/approve this MVP plan.

## Proposed custom skills — not authored

AGENTS.md already proposes eleven specialist names: devguard-laravel-control-plane, devguard-github-integration, devguard-react-ux, devguard-universal-discovery, devguard-runner-isolation, devguard-ai-test-planning, devguard-ai-fix-approvals, devguard-quota-billing, devguard-artifact-privacy, devguard-platform-adapters and devguard-observability-release. They remain NOT AUTHORED; none may be listed as installed. Avoid creating all eleven upfront.

Prioritize only if repeated implementation/review needs justify reusable rules: (1) runner isolation + artifact privacy; (2) GitHub authority + scoped Fix approvals; (3) Laravel current RBAC + atomic usage; then React/FastAPI test planning. Proposed scope should capture repo-specific invariants, exact safe tools/evidence and deny cases rather than repeat framework tutorials. Later-platform adapters stay deferred.

Before any future authoring/install: explicit bounded scope; use skill-creator/writing-skills; pressure-test with denial/injection/concurrency scenarios; human review; review scripts/network/secret access; pin provenance/version/license; verify discovery independently in Codex and Antigravity; only then mark validated. Development-agent skills are not the product Runtime QA registry and never authorize code execution or providers.

## Integration verdict

CLI help/version launch is verified. The initial print probe timed out, but the corrected no-tool plan-mode probe returned exactly DEVGUARD_READ_ONLY_OK with exit 0. See MVP_PROGRESS.md and AGENT_WORKFLOW.md for both results and outer-sandbox limitations. Direct model-response handoff is verified; autonomous coding delegation, actual skill activation and Windows confinement are not verified.
