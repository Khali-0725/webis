# Admin Action Icons and Chat Language Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace red text actions in Admin Users with neutral icons and block common Tagalog/English profanity in chat sends and edits.

**Architecture:** Reuse the existing `Icon`, `Button`, and `ConfirmDialog` components. Add a `block_rules` entry to `config/chat.php`; both chat paths already call `ChatModerationService`. Give profanity its own error copy in `MessagingService`.

**Tech Stack:** React 18, Vitest, Laravel 13, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-01-admin-actions-chat-language-design.md`

## Global Constraints

- No new dependency or database migration.
- Keep explicit confirmation dialog text, reason fields, and typed `DELETE` requirement.
- Existing chat contact rules and violation logging stay active.
- All shell commands start with `rtk`.

## Review Focus

- Icon-only buttons remain discoverable to screen readers: test accessible names.
- Current admin cannot delete self: preserve disabled state.
- Partial words such as `class` and `putative` remain allowed: unit corpus.
- Spaced or leet profanity is blocked: unit corpus.
- `confirm_override` cannot force a profane send or edit: feature tests.

---

### Task 1: Admin action icons

**Files:** Modify `frontend/src/components/ui/Icon.jsx`, `frontend/src/pages/admin/users/UserListPage.jsx`; test `frontend/tests/userPermanentDelete.test.jsx` and a focused active-user test.

**Interfaces:** `Icon({ name })` gains `terminate` and `trash` glyphs. `Button` and `ConfirmDialog` remain unchanged.

- [ ] Add failing frontend tests for accessible icon actions, absence of visible row labels, and self-delete disabled.
- [ ] Run focused tests; confirm failure.
- [ ] Use neutral `subtle` icon-only buttons for active Terminate/Delete and Trash permanent delete, each with `aria-label` and `title`. Use `primary` confirmation tone for those actions.
- [ ] Run focused tests, lint, and build; confirm success.

### Task 2: Chat profanity filter

**Files:** Modify `backend/config/chat.php`, `backend/app/Services/ChatModerationService.php`, `backend/app/Services/MessagingService.php`, `backend/tests/Unit/Services/ChatModerationServiceTest.php`, and `backend/tests/Feature/Messaging/MessageSendingTest.php`.

**Interfaces:** Existing `ChatModerationService::evaluate(string $body): array` returns `tier=blocked`, `category=other`, `matched_rule=profanity`. Existing send/edit methods return 422 with profanity-specific text.

- [ ] Add failing unit cases for common Tagalog/English profanity, spaced/leet forms, and clean near-matches.
- [ ] Add failing feature cases for rejected send/edit even with `confirm_override=true`, no message write, and a logged blocked attempt.
- [ ] Run focused backend tests; confirm failure.
- [ ] Add one bounded regex rule to `chat.block_rules`, check its original spaced text to avoid a `put a` false positive, and add profanity-specific error copy in both send and edit.
- [ ] Run focused tests and full backend suite; confirm success.

### Task 3: Ship

- [ ] Review diff, run full frontend tests/lint/build and `git diff --check`.
- [ ] Commit the changes and push the approved branch to `master` and `professor-revisions`.
- [ ] Check the deployment and live Admin Users page without terminating or deleting an account.
