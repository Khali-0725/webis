# WEBIS — Reviewer Guide

Simple, plain-English guide to what WEBIS is, what it is built with, and what it can do. Written for a reviewer/panel who wants a fast, non-technical overview before (or instead of) reading the full code.

---

## 1. What is WEBIS?

**WEBIS** = *Web-Based Platform for Independent Service Providers*.

It is a website that connects two kinds of users in **Tanza, Cavite**:

- **Clients** — people who need a service (e.g. home repair, cleaning).
- **Service Providers** — independent workers who offer that service.

Clients can find a provider near their barangay, book a time slot, chat with the provider inside the site, pay and upload proof of payment, and leave a rating after the job. An **Admin** oversees everything (approving providers, handling reports, viewing analytics).

The system is a **capstone / thesis project** built by three students at Cavite State University – CCAT Campus, Rosario, Cavite.

**Status:** all 12 planned phases are complete and the app has been tested live (not just automated tests). Backend has 228 automated tests, frontend has 26 — all passing at last check.

---

## 2. Tech Stack (what it's built with)

Think of the system as **two separate programs that talk to each other over the internet**:

```
[ Browser ]  →  React website (Frontend)  →  Laravel API (Backend)  →  MySQL Database
```

### Frontend (what the user sees — the website)

| Tool | What it's for, in plain terms |
|---|---|
| **React 18** | The main JavaScript library used to build the website's screens/components. |
| **Vite** | The tool that runs the site during development and builds it for production (fast reload while coding). |
| **Tailwind CSS** | Pre-made styling shortcuts (buttons, spacing, colors) so the site looks consistent without writing custom CSS for everything. |
| **React Router** | Handles moving between pages (e.g. from "Home" to "Booking") without reloading the whole browser tab. |
| **TanStack Query** | Manages fetching data from the backend (loading states, caching, auto-refresh) so pages stay up to date. |
| **React Hook Form + Zod** | Handles forms (login, booking, profile) and checks that what the user typed is valid before sending it. |
| **Zustand** | Small storage in the browser that remembers things like "who is currently logged in." |
| **Axios** | Sends the actual requests to the backend (e.g. "get me this provider's info"). |
| **Leaflet + OpenStreetMap** | The interactive map used for picking/showing a service location. |
| **Laravel Echo + Pusher (JS)** | Receives live updates (e.g. new chat message appears instantly, without refreshing the page). |

### Backend (the "brain" — business rules, database access, security)

| Tool | What it's for, in plain terms |
|---|---|
| **Laravel 13 (PHP 8.3+)** | The framework that runs the server: receives requests from the website and decides what to do. |
| **Sanctum** | Handles login sessions using secure browser cookies (safer than storing a login token in the browser's local storage). |
| **MySQL 8** | The database that stores all the data — users, bookings, messages, payments, ratings. |
| **Pusher (PHP server side)** | Sends the "something changed" signal to the frontend for real-time chat. |
| **Resend** | Sends outgoing emails (e.g. verify your email, reset your password). |
| **Flysystem (AWS S3 driver)** | Used to store uploaded files (avatars, ID documents, payment proof) in cloud storage. |

### Testing

| Tool | What it's for |
|---|---|
| **PHPUnit** | Automated tests for the backend (228 tests). |
| **Vitest + Testing Library** | Automated tests for the frontend (26 tests). |

### Where it's hosted (live deployment)

| Piece | Hosted on |
|---|---|
| Frontend website | Vercel |
| Backend API | Render (Singapore server) |
| Database | TiDB Cloud (MySQL-compatible, AWS Singapore) |
| Uploaded files | Backblaze B2 (S3-compatible storage) |
| Uptime check | UptimeRobot pings the backend every 5 minutes so the free-tier server doesn't "fall asleep" |

**Why cookies instead of tokens?** The login session is stored in an `HttpOnly` cookie, meaning malicious JavaScript on the page cannot read it — a deliberate security choice tied to the "Confidentiality" and "Resistance" criteria the thesis is evaluated against (ISO/IEC 25010 software quality standard).

---

## 3. Who Uses the System (User Roles)

| Role | Can do |
|---|---|
| **Client** | Browse/search providers, book a service, message the provider, pay, rate the job, report a problem. |
| **Service Provider** | Set up a profile, list skills and service areas, publish services, set availability, accept/manage bookings, message clients, view earnings, respond to reviews. |
| **Admin** | Approve/reject provider verification documents, manage service categories & barangays, view/handle reports and rule violations, view analytics, manage all users. |

Seeded demo accounts exist for each role (all use password `password123`) so a reviewer can log in and try every role without creating an account.

---

## 4. Main Features (Modules), in Plain English

### Accounts & Login
- Register, log in, log out.
- Email verification and "forgot password" flow (real emails are sent).
- Each account has one role: client, provider, or admin.
- Admin can suspend/activate accounts.

### Provider Profiles & Services
- Providers fill out a profile: bio, skills, which barangays they serve.
- Providers create service listings (title, description, price) and can publish or deactivate them.
- Providers must be **verified** (submit an ID/document, admin approves it) before they're allowed to publish a service.
- Public visitors can search/browse services and providers, filter by category/barangay, and sort (including "nearest to me").

### Booking & Location
- Clients pick an available time slot from the provider's calendar.
- The system prevents double-booking the same slot (uses database row-locking so two people can't grab the same slot at once).
- Each booking has an exact location pin (map), but the pin is only shown to the two people involved in that booking — not shown publicly, to protect the client's home address.
- Bookings move through a defined status flow (e.g. requested → confirmed → completed → cancelled) controlled entirely by the backend, so neither side can fake a status.

### In-App Messaging
- Clients and providers can message each other once there is a booking/conversation between them.
- Messages appear live (real-time), without refreshing the page.
- The system automatically filters out attempts to share outside contact info (like phone numbers) in chat, to keep users transacting through the platform.

### Payments
- Supports QR-code based payment: the client pays via QR (e.g. GCash-style) and uploads a screenshot/proof of payment.
- Admin/provider can verify the payment before the booking is marked complete.

### Ratings & Reviews
- After a completed booking, the client can rate and review the provider.
- Providers can view/respond to reviews on their profile.

### Reports / Violations
- Clients or providers can report a problem (e.g. inappropriate behavior).
- Admin reviews reports and can act on "violations" (warnings, suspensions).

### Admin Console
- Manage users, provider verification queue, service categories, barangays.
- View analytics/charts (bookings over time, revenue, etc. — built with a custom chart component, no 3rd-party paid chart library).
- Audit log of important admin actions.
- Handle reports/violations.

---

## 5. How the Code is Organized (for a quick look inside)

```
WEBIS SYSTEM/
├── backend/     → Laravel API (all business logic + database)
│   ├── app/Http/Controllers/Api/   → grouped by who uses them (Client, Provider, Admin, Public, Auth)
│   ├── app/Services/                → the actual business rules (e.g. BookingStateMachine)
│   ├── app/Policies/                → "is this user allowed to see/edit this record?" checks
│   ├── database/migrations/         → defines every database table
│   └── tests/                       → automated backend tests
│
├── frontend/    → React website
│   └── src/pages/{public,auth,client,provider,admin}/  → one folder per role, one file per screen
│
└── docs/        → requirements, database diagram (ERD), deployment guide, testing notes
```

**Security pattern worth mentioning to a reviewer:** the backend never trusts data coming from the browser for sensitive fields like `role`, `price`, or `status` — those are always decided on the server. Every record a user tries to open is checked against a "Policy" (permission rule) tied to that exact record, not just its ID — this prevents one user from viewing another user's private data by guessing a URL/ID (a common vulnerability called IDOR).

---

## 6. Quick Glossary (for non-technical readers)

- **Frontend** — the part you see and click on in the browser.
- **Backend / API** — the part running on a server that stores data and enforces rules; the frontend talks to it, the user never sees it directly.
- **Database** — where all information is permanently stored (users, bookings, messages, etc.).
- **Real-time** — updates that appear instantly (like a new chat message) without the user refreshing the page.
- **Authentication** — proving who you are (logging in).
- **Authorization** — once logged in, checking what you're *allowed* to do or see.
- **Policy** — a backend rule that decides if a specific user can view/edit a specific record.
- **Migration** — a script that creates or changes a database table.
- **Test suite** — automated checks that run the code and confirm it behaves correctly, without a human clicking through the app.

---

*This document is a plain-English summary for review purposes. For full technical detail, see `README.md`, `backend/BUILD-LOG.md` (build log / architecture decisions), and `docs/PHASE-0-REQUIREMENTS-AUDIT.md` (full requirements spec).*
