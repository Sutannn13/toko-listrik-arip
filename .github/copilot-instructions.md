# GitHub Copilot Instructions - Thin Adapter

Adapter Mode: thin
Adapter Source: .instructions.md
Canonical Snapshot SHA256: 2a2a036b4fb90f9e668163b83614f2339044c0d021a6eaf2f380e626dfe72de0

The canonical policy source for this repository is [.instructions.md](../.instructions.md).

## Required Load Order

1. Read [.instructions.md](../.instructions.md) first.
2. Read baseline rules in [.agent-context/rules/](../.agent-context/rules).
3. Load language profile from [.agent-context/stacks/](../.agent-context/stacks).
4. Load blueprints from [.agent-context/blueprints/](../.agent-context/blueprints) for scaffolding requests.
5. Load domain skills from [.agent-context/skills/](../.agent-context/skills).
6. Load request templates from [.agent-context/prompts/](../.agent-context/prompts).
7. Apply team defaults from [.agent-context/profiles/](../.agent-context/profiles), state awareness from [.agent-context/state/](../.agent-context/state), and thresholds from [.agent-context/policies/](../.agent-context/policies).

## Completion Gate

Run [.agent-context/review-checklists/pr-checklist.md](../.agent-context/review-checklists/pr-checklist.md) before declaring work complete.
