---
name: ai-context
description: >
  Shared cross-AI/cross-session project log. Reads and writes AI_CONTEXT.md at the current
  project's root so any AI tool (Claude Code, Cursor, Copilot, another Claude session) editing
  that project can hand off context cheaply — decisions made, gotchas found, scope agreed with
  the user, pending work, test data left in shared systems. Capped at 10 entries so reading it
  never costs much. Works in any project — creates AI_CONTEXT.md on first use if it doesn't exist.
  Use PROACTIVELY: read it before starting non-trivial work in a project, append an entry after
  finishing non-trivial work. Also trigger on "check AI context", "log this for other AIs",
  "what did the other AI do", "read project handoff notes", "/ai-context".
---

# AI Context Log

One file, `AI_CONTEXT.md` at the root of whatever project you're working in, holding a short
reverse-chronological log any AI working on that repo reads and writes. It is NOT a changelog
and NOT a memory dump — git already tracks what changed. This file tracks what the diff *can't*
tell you. Applies to any project you're in — the file lives per-project, not globally.

## When to read

Before starting non-trivial work in the current project (new feature, bug fix that touches more
than a line or two, anything spanning multiple files). Skip for trivial one-liners.

If `AI_CONTEXT.md` doesn't exist at the project root yet, this is the first entry — skip
reading, go write (that first write creates the file).

Read the whole file — it's capped small on purpose (see below), so this is cheap.

## When to write

After finishing non-trivial work, before ending the turn. Prepend a new entry at the **top**
(most recent first). Skip for trivial changes — a typo fix doesn't need an entry.

### What belongs in an entry (high signal only)

- Decisions made and *why* (especially ones a future agent could easily reverse by accident)
- Gotchas / non-obvious facts about the codebase or external systems (schema quirks, legacy
  system behavior, naming inconsistencies, FK constraints that aren't obvious from one table)
- Scope boundaries the user agreed to (what was explicitly deferred/excluded, and why)
- Test/throwaway data left behind in shared systems (a real DB, a real API) that another
  agent or the user needs to know about
- Pending/next work, if the user indicated more is coming

### What does NOT belong (skip these — git already has them)

- Step-by-step account of what you did (that's the diff)
- File lists, line counts, routine implementation detail
- Anything a `git log`/`git diff` read would show just as well

### Entry format

```
## YYYY-MM-DD — <agent/tool name, e.g. "Claude Code (Sonnet 5)">
<1-4 short lines. Fragments fine. No filler.>
```

Keep each entry to a handful of lines — a paragraph, not a report. If it's not worth
remembering in 50 tokens, it's not worth an entry.

### Cap: 10 entries, oldest drop off

Before writing, count `##` headers in the file. If already at 10, delete the oldest (bottom)
entry so the file stays at 10 after your addition. This keeps every future read cheap
regardless of how old the project gets — no unbounded growth, no archive file to manage.

## First use in a project

If `AI_CONTEXT.md` doesn't exist yet at the project root, create it with a one-line header:

```
# AI Context Log

Shared handoff notes for any AI tool editing this project. Capped at 10 entries, most recent first.
```

Then add your first entry below it, same format as any other write.

## Example entry

```
## 2026-07-14 — Claude Code (Sonnet 5)
Switched the payments webhook from polling to a signed callback (`/webhooks/stripe`).
Gotcha: Stripe retries unsigned/failed callbacks for 72h — idempotency key on the handler is load-bearing, don't remove it "for simplicity".
Deferred (user OK'd): refunds still go through the old admin panel, not migrated yet.
Left test data: a test customer (`cus_test_ai`) in the Stripe test account — user will clean up.
```
