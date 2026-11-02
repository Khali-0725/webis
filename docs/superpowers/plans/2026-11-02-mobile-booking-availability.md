# Mobile booking and availability implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make booking navigation, account appeal, availability, and data lists work cleanly on phones.

**Architecture:** Reuse existing booking and availability APIs. Introduce one responsive table component and mobile styles, then migrate existing table surfaces. Keep action controls compact through the existing icon component.

**Tech Stack:** React 18, Tailwind CSS, Vitest, Laravel 13.

**Spec:** `docs/superpowers/specs/2026-11-02-mobile-booking-availability-design.md`

## Global Constraints

- Defer "bottom stock".
- Preserve desktop table layout and existing backend records.
- No new dependencies or migrations.

## Review Focus

- Booking-list route must not link to itself.
- Wrong-password and terminated-account errors must not reveal the appeal link.
- Mobile cards need labels for every value and working row actions.
- Existing custom-hours exceptions must remain editable.
- Long emails and record names must wrap inside phone width.

---

### Task 1: Booking back link and appeal state

**Files:** `frontend/src/layouts/DashboardLayout.jsx`, `frontend/src/pages/auth/LoginPage.jsx`, `backend/app/Services/AuthService.php`, focused frontend/backend tests.

- [x] Add tests for booking detail/list link destinations and suspended-only appeal visibility.
- [x] Confirm tests fail against current behavior.
- [x] Implement route-specific link and suspended-only appeal trigger, including Google login status.
- [x] Run focused tests.

### Task 2: Responsive tables and compact record actions

**Files:** `frontend/src/components/ui/ResponsiveTable.jsx`, `frontend/src/index.css`, table pages under `frontend/src/pages`, `frontend/src/pages/admin/users/UserListPage.jsx`.

- [x] Add a focused test for mobile card labels derived from headers and icon action names.
- [x] Confirm test fails against current behavior.
- [x] Convert table surfaces to responsive tables and compact user-row actions.
- [x] Check phone viewport at 390 px and 320 px using the Providers table fixture.

### Task 3: Availability module

**Files:** `frontend/src/pages/provider/availability/AvailabilityPage.jsx`, `frontend/src/pages/public/ServiceDetailPage.jsx`, focused frontend tests.

- [x] Add a test for unified schedule/exclusion headings.
- [x] Confirm test fails against current behavior.
- [x] Reorganize existing controls into one module, clarify date/time labels and unavailable dates, retain custom hours.
- [x] Run focused tests.

### Task 4: Verify and deploy

- [x] Run frontend tests, lint, and production build; run affected backend tests.
- [x] Inspect Providers table at 390 px and 320 px; neither produced page overflow.
- [x] Commit and push to the active GitHub deployment branch.
- [x] Verify Vercel/Render deployment and live health.
