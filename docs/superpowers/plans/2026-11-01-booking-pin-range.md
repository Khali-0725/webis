# Booking Pin Range Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prevent booking pins farther than 2 km from the selected barangay's approximate center.

**Architecture:** A server-side booking request validation guard is authoritative. The frontend applies the same distance rule at pin selection and before submission and shows the allowed circle on the map. Barangay changes reset the pin.

**Tech Stack:** Laravel 13, PHP, React, Leaflet, PHPUnit, Vitest.

**Spec:** `docs/superpowers/specs/2026-11-01-neutral-actions-booking-pin-design.md`

## Global Constraints

- The selected barangay is authoritative; GPS is optional.
- The maximum distance is 2 km. Missing center coordinates reject booking.
- Approximate centers are not exact barangay boundaries; do not claim precise containment.
- A rejected request must not create a booking or payment.

## Review Focus

- China and other distant coordinates must fail via the API, even if the UI is bypassed.
- Nearby coordinates must still book if other booking rules pass.
- Missing or inactive barangay must fail clearly.
- Changing barangay must reset the pin and confirmation.
- Device GPS and map clicks must follow the same range rule.

---

### Task 1: Server guard

**Files:** `backend/app/Http/Requests/Booking/StoreBookingRequest.php`, `backend/tests/Feature/Booking/BookingCreationTest.php`.

- [x] Add failing feature tests for distant and missing-center pins, plus a valid nearby pin.
- [x] Run focused tests and confirm failures.
- [x] Validate Haversine distance to the selected active barangay center in the request.
- [x] Update existing booking test payloads to use their selected centers, then run focused and full backend tests.

### Task 2: Map and booking form

**Files:** `frontend/src/components/map/LocationPicker.jsx`, `frontend/src/pages/public/ServiceDetailPage.jsx`, new frontend tests for distance helper and booking form guard.

- [x] Add failing tests for out-of-range rejection and barangay change reset.
- [x] Run focused tests and confirm failures.
- [x] Show the 2 km allowed circle; reject out-of-range picks and prevent submit with a clear message.
- [x] Run focused and full frontend tests, lint, and build.

### Task 3: Deploy

- [ ] Review diffs, commit, and push to `master` and `professor-revisions`.
- [ ] Verify Vercel and Render deploys and the live health endpoint.
