# Agent Project Guide & Rules

This project uses the **ECC (Everything Claude Code / Agent Harness)** optimization system configured for **Google Antigravity 2.0** with the **minimal** profile.

---

## 1. Environment & Architecture

- **Agent Harness:** Google Antigravity 2.0
- **Primary Task Source:** [`todo.md`](file:///c:/xampp/htdocs/adminDashboard/todo.md) (All development phases, task checklists, and Agent Logs are tracked here).
- **Scope & Constraints:**
  - **Local First:** All active work targets local XAMPP Apache, MariaDB, and PHP 8.x.
  - **Server Deferred:** Production server deployment, VPS, Docker, Nginx, and remote CI/CD are strictly **DEFERRED** (Phase 10 & Appendices H–I are draft references only).
- **Workspace Customizations:** [`.agents/`](file:///c:/xampp/htdocs/adminDashboard/.agents/)
  - Rules: [`.agents/rules/`](file:///c:/xampp/htdocs/adminDashboard/.agents/rules/)
  - Workflows: [`.agents/workflows/`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/)
  - Skills: [`.agents/skills/`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/)
  - Agents: [`.agents/agents/`](file:///c:/xampp/htdocs/adminDashboard/.agents/agents/)
- **Target OS / Shell:** Windows (PowerShell)
- **Local Server:** XAMPP Apache VirtualHost [`http://admin.test/`](http://admin.test/) (Fallback: `http://localhost/adminDashboard/public/`)
- **Backend Stack:** PHP 8.x (CLI & Apache module) with PDO MySQL (`config/config.php`, `config/auth.php`)
- **Database:** MariaDB / MySQL 10.4+ (`bps_solsel_dev` for dev, `bps_solsel_test` for automated testing; schema in `database/schema.sql`, seed in `database/seeds/dev_seed.sql`)
- **Frontend:** Vanilla PHP / HTML5, CSS, JavaScript (No heavy frontend framework)
- **Testing:** Vitest (`tests/app.test.js`), PHP verification scripts, and PHPUnit

---

## 2. Rule Precedence & Active Standards

> **Rule of Precedence (Section 4A):** If `.agents/rules/*` and [`todo.md`](file:///c:/xampp/htdocs/adminDashboard/todo.md) differ, ALWAYS follow the **stricter** rule in `todo.md`.

### Universal Standards
- **Immutability & Safety:** Avoid unnecessary global state mutation; keep functions pure, modular, and single-purpose.
- **Error Handling:** Explicit error handling; never silently suppress exceptions, query errors, or validation failures. Log errors securely into `storage/logs/`.
- **KISS, YAGNI & Ponytail Mindset:** Channel the lazy senior developer mindset (`.agents/rules/ponytail.md`). Choose straightforward, maintainable native solutions over complex speculative abstractions. Deletion over addition; fewest files possible; stdlib/native features over new dependencies.
- **Input Validation & Sanitization:** Validate and sanitize all external inputs at the system boundary before processing.
- **Git Hygiene:** One branch per phase (`fix/fase-X`), one task = one small commit with conventional commit format: `tipe(area): ringkasan`. Never commit `.env`, logs, or production database dumps.

### PHP Standards
- **Code Style:** Follow PSR-12 conventions.
- **Database Access:** ALWAYS use prepared statements with PDO parameter binding (`$stmt->prepare()`, `$stmt->execute([...])`). **NEVER concatenate untrusted variables or strings into SQL queries.**
- **Security:**
  - Prevent XSS by using `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` on all output.
  - Enforce authentication and role checks on all administrative endpoints (`config/auth.php`) before processing.
  - State-mutating requests (POST, PUT, DELETE) MUST require CSRF verification. Never allow data mutation via GET requests.
  - Validate MIME types, extensions, and sanitize filenames for file uploads (`public/uploads/` / `storage/uploads/`).

### Web & JavaScript Standards
- Clean separation of DOM manipulation, styles, and application logic.
- Verify API payloads, HTTP status codes, and network error handling in frontend AJAX / fetch calls.
- Preserve backward compatibility for App B (`sipasti.bps1310.cloud`) consuming API endpoints in `public/api/`.

---

## 3. Key Available Workflows (Slash Commands)

Use these workflows during development and review tasks:

| Workflow / Command | File / Reference | Purpose |
|---|---|---|
| `/plan` | [`.agents/workflows/plan.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/plan.md) | Formulate step-by-step implementation plans before making changes. |
| `/build-fix` | [`.agents/workflows/build-fix.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/build-fix.md) | Incrementally diagnose and resolve syntax or build errors. |
| `/code-review` | [`.agents/workflows/code-review.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/code-review.md) | Structured multi-axis code review. |
| `/verification-loop` | [`.agents/workflows/verification-loop.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/verification-loop.md) | Comprehensive verification across tests, linting, and security. |
| `/security-scan` | [`.agents/workflows/security-scan.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/security-scan.md) | Security scan against permissions, vulnerabilities, and secrets. |
| `/test-coverage` | [`.agents/workflows/test-coverage.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/test-coverage.md) | Analyze test coverage and identify untested paths. |
| `/auto-update` | [`.agents/workflows/auto-update.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/auto-update.md) | Update ECC target files to the latest release. |
| `/ponytail-review` | [`.agents/skills/ponytail-review/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-review/SKILL.md) | Review code specifically to eliminate over-engineering and bloat. |
| `/ponytail-audit` | [`.agents/skills/ponytail-audit/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-audit/SKILL.md) | Whole-repo audit identifying what to delete or replace with stdlib. |
| `/ponytail-debt` | [`.agents/skills/ponytail-debt/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-debt/SKILL.md) | Harvest `ponytail:` comments into a debt ledger. |

---

## 4. Installed Skills Summary

### 4.1 ECC Core & Engineering Quality
- **`tdd-workflow`** ([`.agents/skills/tdd-workflow/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/tdd-workflow/SKILL.md)): Test-driven development loop: write failing test, write minimal code to pass, refactor.
- **`click-path-audit`** ([`.agents/skills/click-path-audit/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/click-path-audit/SKILL.md)): Trace UI state transitions and user touchpoints across all pages.
- **`architecture-decision-records`** ([`.agents/skills/architecture-decision-records/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/architecture-decision-records/SKILL.md)): Record key architectural decisions as numbered ADRs in `docs/adr/`.
- **`error-handling`** ([`.agents/skills/error-handling/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/error-handling/SKILL.md)): Structured patterns for error handling, exceptions, and user-facing messages.
- **`delivery-gate`** ([`.agents/skills/delivery-gate/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/delivery-gate/SKILL.md)): Quality gating before completing phases and releases.
- **`agent-self-evaluation`** ([`.agents/skills/agent-self-evaluation/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/agent-self-evaluation/SKILL.md)): Self-rate output across accuracy, completeness, clarity, actionability, and conciseness.

### 4.2 Codebase Understanding & Architecture Knowledge Graph
- **`graphify`** ([`.agents/skills/graphify/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/graphify/SKILL.md)): Deterministic AST parsing of codebase, SQL schemas, markdown docs, and configs into a queryable knowledge graph without vector database dependencies.

### 4.3 Minimalist Architecture & Anti-Overengineering (Ponytail)
- **`ponytail`** ([`.agents/skills/ponytail/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail/SKILL.md)): Forces the simplest, shortest, minimal solution (lazy senior developer mode).
- **`ponytail-review`** ([`.agents/skills/ponytail-review/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-review/SKILL.md)): Scans diffs to prune speculative abstractions, dead code, and unneeded dependencies.
- **`ponytail-audit`** ([`.agents/skills/ponytail-audit/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-audit/SKILL.md)): Repository-wide over-engineering inspection and simplification opportunities.
- **`ponytail-debt`** ([`.agents/skills/ponytail-debt/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-debt/SKILL.md)): Tracks deliberate simplifications marked with `ponytail:`.
- **`ponytail-gain`** ([`.agents/skills/ponytail-gain/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ponytail-gain/SKILL.md)): Reports metrics on code reduction and complexity savings.

### 4.4 UI Craft, Design Engineering & Micro-Interactions (Emil Kowalski)
- **`emil-design-eng`** ([`.agents/skills/emil-design-eng/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/emil-design-eng/SKILL.md)): Interaction design taste, polish, and frontend craftsmanship principles.
- **`animate`** ([`.agents/skills/animate/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/animate/SKILL.md)): Micro-interaction physics, spring animations, and CSS/JS transitions.
- **`animation-vocabulary`** ([`.agents/skills/animation-vocabulary/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/animation-vocabulary/SKILL.md)): Precision terminology for choreography, easing curves, and duration tokens.
- **`ask-sonner`** ([`.agents/skills/ask-sonner/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/ask-sonner/SKILL.md)): Unobtrusive toast notifications and subtle UI state feedback.
- **`break-ui`** ([`.agents/skills/break-ui/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/break-ui/SKILL.md)): Stress-test UI components with long text, extreme viewports, missing states, and edge cases.
- **`find-animation-opportunities`** ([`.agents/skills/find-animation-opportunities/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/find-animation-opportunities/SKILL.md)): Audit UI flows to identify moments where subtle motion clarifies state changes.
- **`improve-animations`** ([`.agents/skills/improve-animations/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/improve-animations/SKILL.md)): Refine clumsy or linear animations into fluid, organic transitions.
- **`prototype`** ([`.agents/skills/prototype/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/prototype/SKILL.md)): Rapid interactive UI prototyping patterns.
- **`review-animations`** ([`.agents/skills/review-animations/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/review-animations/SKILL.md)): 60fps performance and sensory feedback review for interface animations.
- **`apple-design`** ([`.agents/skills/apple-design/SKILL.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/apple-design/SKILL.md)): Clean typography, deliberate whitespace, and understated hierarchy.

