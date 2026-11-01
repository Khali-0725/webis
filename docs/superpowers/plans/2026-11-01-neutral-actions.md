# Neutral Actions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace red action buttons with compact neutral icons in lists and toolbars, while keeping clear confirmation text.

**Architecture:** Reuse `Button`, `Icon`, and `ConfirmDialog`. Add one small labeled icon-button wrapper for list actions. Make any remaining danger-styled button neutral at the shared style layer.

**Tech Stack:** React, Tailwind, Vitest, Testing Library.

**Spec:** `docs/superpowers/specs/2026-11-01-neutral-actions-booking-pin-design.md`

## Global Constraints

- No red buttons. Red error text and alerts may remain.
- Compact actions need `aria-label` and `title`; confirmation dialogs keep written action names.
- Preserve disabled/loading behavior and existing confirmation safeguards.

## Review Focus

- Icon-only controls must remain understandable to keyboard and screen-reader users.
- Archive count, restore, delete, and reject actions must retain their handlers.
- Confirmations must remain explicit and not execute on the first icon click.
- No active red button may remain, including Logout and form actions.
- The original untracked master prompt must stay untouched.

---

### Task 1: Shared compact actions

**Files:** `frontend/src/components/ui/Icon.jsx`, `frontend/src/components/ui/IconActionButton.jsx`, `frontend/src/components/ui/RowActions.jsx`, `frontend/src/components/ui/BulkArchiveControls.jsx`, `frontend/tests/bulkArchiveControls.test.jsx`, new `frontend/tests/rowActions.test.jsx`.

- [x] Add failing tests for neutral icon actions, accessible names, and preserved archive confirmation.
- [x] Run focused tests and confirm failures.
- [x] Add compact icon wrapper and glyphs; convert shared row and bulk actions.
- [x] Run focused tests and confirm passes.

### Task 2: Remaining red actions

**Files:** All frontend pages using `variant="danger"` or red button classes, `frontend/src/components/layout/Sidebar.jsx`, `frontend/src/components/ui/Button.jsx`.

- [x] Replace compact list actions with icon buttons; keep text in dialogs and form confirmations with neutral/navy styling.
- [x] Change shared danger button style to neutral to prevent stray red buttons.
- [x] Scan JSX for red button classes and run frontend tests, lint, and build.
