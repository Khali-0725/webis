# Professor System Revisions Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans task by task. Steps use checkbox syntax for tracking.

**Goal:** Apply the professor's profile, navigation, content, moderation, availability, chat, archive, and mobile receipt changes.

**Architecture:** Keep Laravel as the permission and validation authority. React reflects server state. Preserve existing records through migrations and soft deletes. Separate Super Admin privileges from daily Admin moderation.

**Tech Stack:** Laravel 13, PHP, MySQL/SQLite tests, React 18, Vite, Vitest.

**Spec:** Conversation requirements through 2026-11-05, including the provider review and one-report clarification.

## Global Constraints

- Every shell command begins with `rtk`.
- A reporter can file only once against the same person, including after withdrawing a report. A service or booking report must not bypass that rule.
- Ten distinct reporting clients and at least 85% of eligible completed clients trigger an administrator review; reports alone do not automatically suspend.
- Admin can only soft delete. Super Admin alone can permanently delete archived records, with confirmation and audit history.
- Only Admin handles review moderation; Super Admin owns CMS, admin accounts, and archive controls.
- Preserve existing records and authorize every sensitive API action on the server.

## Review Focus

- Repeated reports through different target types must be blocked: test service, provider, user, and booking paths.
- Withdrawn reports must still count for duplicate prevention: test soft deleted rows.
- Existing Admin tokens must not gain Super Admin powers: test forbidden API responses.
- Mobile receipt download must return a usable PDF instead of opening a blocked tab: test browser download flow.
- Existing provider replies must remain readable as history, but no new reply may be posted.

---

### Task 1: Report and review controls

**Files:** `backend/app/Http/Controllers/Api/ReportController.php`, `backend/app/Http/Requests/StoreReportRequest.php`, `backend/app/Http/Controllers/Api/ReviewController.php`, `backend/routes/api.php`, `frontend/src/pages/provider/reviews/ReviewListPage.jsx`, `frontend/src/pages/bookings/BookingDetailPage.jsx`, `frontend/src/pages/public/ProviderProfilePage.jsx`, `frontend/src/pages/client/reviews/ReviewListPage.jsx`, `backend/tests/Feature/Report/ReportSubmissionAndResolutionTest.php`, `backend/tests/Feature/Review/ReviewReplyTest.php`.

- [ ] Write failing tests for one report per reporter and person across target types, including withdrawn reports.
- [ ] Run focused report tests and confirm expected failures.
- [ ] Enforce duplicate prevention inside the report transaction, with a database constraint where possible.
- [ ] Remove provider reply routes/actions; keep old reply text read only.
- [ ] Test reports and removed reply endpoints, then run related suites.

### Task 2: Super Admin and archives

**Files:** `backend/app/Enums/UserRole.php`, `backend/app/Models/User.php`, `backend/routes/api.php`, `backend/app/Http/Controllers/Api/Admin/UserController.php`, new migration and feature tests, `frontend/src/constants/index.js`, `frontend/src/components/layout/Sidebar.jsx`, admin user pages.

- [ ] Write failing tests: Admin cannot permanently delete or manage Admin accounts; Super Admin can, with audit.
- [ ] Run focused tests and confirm expected failures.
- [ ] Add Super Admin role and server permissions; migrate existing bootstrap administrator safely.
- [ ] Give Super Admin CMS and archive controls while Admin keeps review moderation.
- [ ] Expose role-aware navigation and controls; verify both roles.

### Task 3: Profile, landing page, and search

**Files:** profile request/controller/resource and migration, profile page, landing page, announcement/settings API, sidebar/search pages, feature and frontend tests.

- [ ] Write failing tests for editable address/contact/email and controlled landing content.
- [ ] Run focused tests and confirm expected failures.
- [ ] Implement API validation and profile form; keep one profile entry at the clickable avatar.
- [ ] Keep authenticated provider search inside the account; remove guest search from landing.
- [ ] Test user and Super Admin flows.

### Task 4: Chat, availability, navigation, mobile receipt

**Files:** messaging pages and API, availability pages/API, back links, receipt download helper, related frontend/backend tests.

- [ ] Write failing tests for active-only auto replies, compressed availability, and phone PDF download.
- [ ] Run focused tests and confirm expected failures.
- [ ] Implement generic answers with account-active gating and fixed composer on phone.
- [ ] Shorten availability input, correct parent-module Back links, and download receipt as PDF on phone.
- [ ] Run related suites and inspect mobile layout.

### Task 5: Integration and release

- [ ] Run full backend tests, frontend tests, lint, and production build.
- [ ] Review changes against every requirement and inspect responsive screens.
- [ ] Commit only intended files, push to `master` and `professor-revisions`, and verify Vercel deployment.
