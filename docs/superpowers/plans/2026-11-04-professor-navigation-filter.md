# Navigation, Filtering, and Service Results Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Simplify WEBIS navigation and filters, and present service search in a responsive table.

**Architecture:** Keep existing APIs and pages. Add client dashboard routes around shared public search/detail components; group sidebar entries; extend shared admin date controls; replace service result cards with ResponsiveTable.

**Tech Stack:** React, React Router, Tailwind, Vitest.

**Spec:** `docs/superpowers/specs/2026-11-04-professor-navigation-filter-design.md`

## Global Constraints

- Keep all existing admin destinations.
- Keep public search route for guests.
- No horizontal page scroll at 320 px and 390 px.
- Date preset ranges include today and use local dates.

## Review Focus

- Sidebar active styling and mobile drawer navigation.
- Account Profile is reachable without duplicate sidebar entry.
- Client service detail remains inside portal and booking links work.
- Preset dates do not overwrite manual ranges unexpectedly.
- Wide service data wraps on mobile cards.
- Booking date filters are labeled Scheduled, matching the API's scheduled date filter.
- Long quote prices fit the results area at 1024 px without an inner scrollbar.

### Task 1: Navigation and client search

**Files:** Modify `frontend/src/components/layout/Sidebar.jsx`, `frontend/src/layouts/DashboardLayout.jsx`, `frontend/src/routes/index.jsx`, `frontend/src/pages/public/SearchResultsPage.jsx`; test `frontend/tests/professorNavigation.test.jsx`.

- [x] Write failing tests for grouped links, avatar profile link, no Home/Profile entries, and client search/detail routes.
- [x] Implement grouped nav and client routes.
- [x] Run focused tests.

### Task 2: Admin filters

**Files:** Modify `frontend/src/components/ui/AdminRecordFilters.jsx`, `frontend/src/pages/admin/bookings/BookingListPage.jsx`, `frontend/src/pages/admin/providers/ProviderListPage.jsx`, `frontend/src/pages/admin/services/ServiceListPage.jsx`; test `frontend/tests/adminRecordFilters.test.jsx` and `frontend/tests/adminBookingFilters.test.jsx`.

- [x] Write failing tests for 3/5 month and custom ranges and single Bookings/Providers/Services toolbars.
- [x] Implement presets and combined Bookings/Providers/Services toolbars.
- [x] Run focused tests.

### Task 3: Service result table

**Files:** Modify `frontend/src/pages/public/SearchResultsPage.jsx`; test `frontend/tests/serviceResultsTable.test.jsx`.

- [x] Write failing test for table columns, responsive cards, and client detail link.
- [x] Implement table with existing ResponsiveTable.
- [x] Run focused tests.

### Task 4: Verification and release

- [x] Run frontend tests, lint, and build.
- [x] Inspect 320 px and 390 px layouts and verify no horizontal page scroll.
- [x] Review diff; fix material findings.
- [x] Commit, push master and professor-revisions, and verify live deployment.
