# Prompt: Review Code

> Copy-paste this prompt when you want the AI to self-review its own code
> or review code you've written.

---

## The Prompt

```
Run a comprehensive code review on the current codebase (or the files I'm about to show you).

Use these checklists:
1. Read .agent-context/review-checklists/pr-checklist.md — apply every item.
2. Read .agent-context/review-checklists/security-audit.md — apply every item.
3. Apply documentation scope rules exactly: This applies to documentation, release notes, onboarding text, review summaries, and agent-facing explanations.
4. Treat scope-style findings as advisory unless they hide factual errors, contract mismatches, or non-negotiable violations.
5. Enforce documentation hard blockers on changed boundaries: public surface changes, API contract changes, and database structure changes must include synchronized documentation updates.
6. Enforce context-triggered strict audits: review requests, PR-intent workflows, and major feature completion must run strict security and performance audits; small edits stay lightweight unless strict mode is explicitly forced.
7. Enforce cross-session consistency guardian: session handoff must include active architecture contract summary, drift detection must warn before direction changes, and direction changes require explicit user confirmation.
8. Enforce explain-on-demand state visibility: default responses must avoid unnecessary state-file internals, state internals are exposed only on explicit request, and diagnostic mode must explain relevant state decisions when needed.

For EVERY violation found:
- State the exact file and line
- Reference the specific rule (file + section)
- Explain WHY it's a problem (not just "it violates the rule")
- Provide the corrected code

Output format:
## PR REVIEW RESULTS
PASS [Item]
FAIL [Item] (with Reasoning Chain)

## SECURITY AUDIT RESULTS
CRITICAL/HIGH/MEDIUM/LOW [Finding] — severity + fix

VERDICT: PASS / FAIL
```

## Quick Review (Subset)

If you want a faster review focusing on the most critical items:

```
Quick review the current code. Check ONLY:
1. Any use of `any` type? (rules/stacks/typescript.md)
2. Any empty catch blocks? (rules/error-handling.md)
3. Any N+1 queries? (rules/performance.md)
4. Any hardcoded secrets? (rules/security.md)
5. Any missing input validation? (rules/security.md)

Use the Reasoning Clause for every finding.
```
