# Gemini Instructions - Thin Adapter

Adapter Mode: thin
Adapter Source: .instructions.md
Canonical Snapshot SHA256: 08c326d79f2c0f4bd7ef106da3542ab0f78f4d02bc205d63598db34cf3f40731

Canonical policy source: [.instructions.md](../.instructions.md).

## Bootstrap Sequence

1. Load [.instructions.md](../.instructions.md) first.
2. Apply baseline rules from [.agent-context/rules/](../.agent-context/rules).
3. Load request templates from [.agent-context/prompts/](../.agent-context/prompts).
4. Apply review contracts from [.agent-context/review-checklists/](../.agent-context/review-checklists).
5. Apply state awareness from [.agent-context/state/](../.agent-context/state) and policy thresholds from [.agent-context/policies/](../.agent-context/policies).
6. Resolve stack and architecture choices dynamically from project context docs plus live evidence.

## Completion Gate

Run [.agent-context/review-checklists/pr-checklist.md](../.agent-context/review-checklists/pr-checklist.md) before declaring completion.
