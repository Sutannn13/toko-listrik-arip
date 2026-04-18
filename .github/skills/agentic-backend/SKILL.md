---
name: agentic-backend
description: "Use when: backend, API, Laravel, validation, migrations, query optimization, auth flow, reset password, security hardening, server bug fixing."
---

# Agentic Backend

Source pack: `.agent-context/skills/backend/`

## Load These Files

1. `.agent-context/skills/backend/README.md`
2. `.agent-context/skills/backend/architecture.md`
3. `.agent-context/skills/backend/validation.md`
4. `.agent-context/skills/backend/data-access.md`
5. `.agent-context/skills/backend/errors.md`

## Workflow

1. Keep strict transport -> service -> repository boundaries.
2. Validate at request boundaries before business logic.
3. Prevent data leaks in error responses.
4. Add or update tests for changed behavior.
