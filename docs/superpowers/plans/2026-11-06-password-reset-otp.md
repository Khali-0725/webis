# Email OTP Password Reset Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Replace WEBIS password-reset links with a six-digit email OTP and a verified reset grant.

**Architecture:** A dedicated reset challenge row stores hashed code and grant. The API sends, verifies, and consumes it; React presents each step.

**Tech Stack:** Laravel, PHP, MySQL, React, Vite.

**Spec:** docs/superpowers/specs/2026-11-06-password-reset-otp-design.md

## Global Constraints
- OTP: six digits, expires in 10 minutes, resend after 60 seconds, five failed attempts.
- Unknown emails receive generic responses. Codes and grants never appear in logs or send responses.
- Existing API envelope and authentication styling stay consistent.

## Review Focus
- Concurrent verification attempts must not exceed the failure limit.
- Resend invalidates the old code.
- The grant is one-use and expires.
- A failed mail send does not leave a usable code.
- Deleted accounts cannot reset passwords.

---

### Task 1: Backend challenge and API

**Files:** Create migration for password reset challenges; create PasswordResetOtpService, PasswordResetCodeNotification; modify password controller, routes, tests.

**Interfaces:** equest(string ): void, erify(string , string ): string, eset(string , string , string ): void. API routes: POST /api/auth/forgot-password, /api/auth/verify-reset-code, /api/auth/reset-password.

- [ ] Write failing feature tests for delivery, unknown/deleted email, expiry, resend, failed attempts, one-use grant, successful reset, and old token rejection.
- [ ] Run focused backend tests and confirm failures.
- [ ] Add migration, service, notification, and routes. Store only hashes; use row locks for verify/reset; consume grant on reset.
- [ ] Run focused backend tests and confirm passes.

### Task 2: Frontend OTP flow

**Files:** Modify forgot/reset pages, auth API/hooks, routes as needed.

**Interfaces:** uthApi.verifyResetCode({email,code}) returns a reset grant. Reset call accepts {email,grant,password,password_confirmation}.

- [ ] Build email entry, six-digit code entry with resend, and password form. Keep phone layout within viewport.
- [ ] Run frontend lint, tests, and build.

### Task 3: Verify and deploy

- [ ] Run backend tests and frontend checks.
- [ ] Review changed files and diff check.
- [ ] Commit and push to master and professor-revisions.
- [ ] Confirm Render/Vercel deploys and perform safe live smoke checks.
