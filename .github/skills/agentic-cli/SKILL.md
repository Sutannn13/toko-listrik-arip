---
name: agentic-cli
description: "Use when: CLI command design, command safety, init and upgrade flows, machine-readable output, and automation-friendly scripting."
---

# Agentic CLI

Source pack: `.agent-context/skills/cli/`

## Load These Files

1. `.agent-context/skills/cli/README.md`
2. `.agent-context/skills/cli/init.md`
3. `.agent-context/skills/cli/upgrade.md`
4. `.agent-context/skills/cli/output.md`
5. `.agent-context/skills/cli/safety-telemetry.md`

## Workflow

1. Default to safe and explicit command behavior.
2. Prefer dry-run and validation paths before mutation.
3. Return clear, parseable output for automation.
4. Document failure modes and recovery steps.
