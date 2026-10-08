# Agent Project Guide & Rules

This project uses the **ECC (Everything Claude Code / Agent Harness)** optimization system configured for **Google Antigravity 2.0** with the **minimal** profile.

---

## 1. Environment & Architecture

- **Agent Harness:** Google Antigravity 2.0
- **Workspace Customizations:** [`.agents/`](file:///c:/xampp/htdocs/adminDashboard/.agents/)
  - Rules: [`.agents/rules/`](file:///c:/xampp/htdocs/adminDashboard/.agents/rules/)
  - Workflows: [`.agents/workflows/`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/)
  - Skills: [`.agents/skills/`](file:///c:/xampp/htdocs/adminDashboard/.agents/skills/)
  - Agents: [`.agents/agents/`](file:///c:/xampp/htdocs/adminDashboard/.agents/agents/)
- **Target OS / Shell:** Windows (PowerShell)
- **Local Server:** XAMPP Apache (`http://localhost/adminDashboard/public/`)
- **Backend Stack:** PHP 8.x with PDO MySQL (`config/config.php`, `config/auth.php`)
- **Database:** MySQL / MariaDB (Schema in `database/`)
- **Frontend:** Vanilla PHP / HTML5, CSS, JavaScript
- **Testing:** Vitest (`tests/app.test.js`) and PHP verification scripts

---

## 2. Active ECC Rules & Standards

### Universal Standards (`.agents/rules/common-*.md`)
- **Immutability & Safety:** Avoid unnecessary state mutation; keep functions pure and modular.
- **Error Handling:** Explicit error handling; never silently suppress exceptions or database errors.
- **KISS & YAGNI:** Choose straightforward, maintainable solutions over complex speculative abstractions.
- **Input Validation:** Validate and sanitize all external inputs at the system boundary before processing.

### PHP Standards (`.agents/rules/php-*.md`)
- **Code Style:** Follow PSR-12 conventions.
- **Database Access:** ALWAYS use prepared statements with PDO parameter binding (`$stmt->prepare()`, `$stmt->execute([...])`). Never concatenate untrusted strings into SQL.
- **Security:**
  - Prevent XSS by using `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` on output.
  - Enforce authentication and role checks on all administrative endpoints (`config/auth.php`).
  - Validate MIME types and sanitize filenames for file uploads (`public/uploads/`).

### Web & JavaScript Standards (`.agents/rules/web-*.md`, `.agents/rules/typescript-*.md`)
- Clean separation of DOM manipulation and application logic.
- Verify API payloads and network error handling in frontend AJAX calls.

---

## 3. Key Available Workflows (Slash Commands)

Use these workflows during development and review tasks:

| Workflow | Path | Purpose |
|---|---|---|
| `/plan` | [`.agents/workflows/plan.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/plan.md) | Formulate step-by-step implementation plans before making changes. |
| `/build-fix` | [`.agents/workflows/build-fix.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/build-fix.md) | Incrementally diagnose and resolve syntax or build errors. |
| `/code-review` | [`.agents/workflows/code-review.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/code-review.md) | Structured multi-axis code review. |
| `/verification-loop` | [`.agents/workflows/verification-loop.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/verification-loop.md) | Comprehensive verification across tests, linting, and security. |
| `/security-scan` | [`.agents/workflows/security-scan.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/security-scan.md) | Security scan against permissions, vulnerabilities, and secrets. |
| `/tdd-workflow` | [`.agents/workflows/go-test.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/tdd-workflow.md) | Test-driven development loop. |
| `/auto-update` | [`.agents/workflows/auto-update.md`](file:///c:/xampp/htdocs/adminDashboard/.agents/workflows/auto-update.md) | Update ECC target files to the latest release. |

---

## 4. Installed Skills Summary

ECC minimal profile provides essential engineering skills loaded on demand:
- **`agent-self-evaluation`**: Assess completed tasks across accuracy, completeness, and clarity.
- **`error-handling`**: Patterns for structured error handling, retries, and failure states.
- **`tdd-workflow`**: Test-driven development workflows.
- **`click-path-audit`**: Trace UI state transitions and user touchpoints.
- **`architecture-decision-records`**: Record key architectural decisions.
- **`delivery-gate`**: Quality gating before completing complex deliverables.
