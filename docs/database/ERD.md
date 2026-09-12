# WEBIS — Entity Relationship Diagram (Phase 2)

**Date:** 2026-09-10
**Tables:** 24 (+ 4 framework tables)
**Status:** ✅ Complete

---

## 1. Entity Overview

```
CORE: users ──▶ barangays
              └─ 1:1 ──▶ provider_profiles
                           ├── provider_skills
                           ├── provider_verification_documents
                           ├── provider_service_areas ◀─ barangays
                           ├── provider_availability_rules
                           ├── provider_availability_exceptions
                           ├── services ──▶ service_categories
                           └── provider_payment_methods

TRANSACTIONS: bookings ──▶ booking_status_histories (append-only)
                        ├── booking_locations (1:1, privacy-gated)
                        ├── payments ──▶ payment_proofs (1:N)
                        └── reviews (1:1, UNIQUE booking_id)

MESSAGING: conversations ──▶ messages
                          └── chat_violations

ADMIN/AUDIT: audit_logs (polymorphic), reports (polymorphic),
             system_settings, notifications (Laravel morphs)
```

---

## 2. Table Catalog (24 tables)

| Table | Key Columns | Soft Del |
|-------|-------------|----------|
| users | id, names, email(UQ), role, status, barangay_id(FK) | ✅ |
| barangays | id, name, municipality, lat/lng, is_active | ✗ |
| provider_profiles | id, user_id(UQ), verification_status, rating_avg/count | ✅ |
| provider_skills | id, provider_profile_id(FK), skill | ✗ |
| provider_verification_documents | id, provider_profile_id(FK), type, status | ✗ |
| service_categories | id, name, slug(UQ), min/max_price | ✅ |
| services | id, provider_profile_id(FK), category_id(FK), pricing_type, price | ✅ |
| provider_service_areas | id, provider_profile_id(FK), barangay_id(FK) | ✗ |
| provider_availability_rules | id, provider_profile_id(FK), day, start/end | ✗ |
| provider_availability_exceptions | id, provider_profile_id(FK), date | ✗ |
| bookings | id, code(UQ), client_id(FK), provider_profile_id(FK), status | ✅ |
| booking_status_histories | id, booking_id(FK), from/to_status | ✗ |
| booking_locations | id, booking_id(UQ,FK), lat/lng, address | ✗ |
| conversations | id, client_id(FK), provider_user_id(FK) UQ pair | ✗ |
| messages | id, conversation_id(FK), sender_id(FK), body | ✗ |

## 3. Seed Data Summary

| Seeder | Records | Notes |
|--------|---------|-------|
| DemoAccountSeeder | 4 users | admin, client, provider, suspended |
| BarangaySeeder | 41 barangays | All Tanza, Cavite with approx. coords |
| ServiceCategorySeeder | 8 categories | Plumbing → General Maintenance |
| DemoDataSeeder | 20 clients, 12 providers, ~30 services, 33 bookings, reviews, payments, conversations | Full lifecycle |

## 4. Design Notes

1. **Privacy by architecture**: `booking_locations` is separate from `bookings` — coordinates cannot leak via Booking resource.
2. **Soft deletes**: Only on entities with historical significance (users, profiles, services, categories, bookings).
3. **Cached aggregates**: `provider_profiles.rating_avg/count/completed_bookings_count` — maintained transactionally.
4. **Append-only**: `booking_status_histories` and `audit_logs` — no update path.
5. **FK strategy**: `cascadeOnDelete` for owned sub-records, `restrictOnDelete` for business records, `nullOnDelete` for audit trails.

| chat_violations | id, user_id(FK), category, severity, action | ✗ |
| provider_payment_methods | id, provider_profile_id(FK), type | ✗ |
| payments | id, booking_id(UQ,FK), amount, status | ✗ |
| payment_proofs | id, payment_id(FK), file_path | ✗ |
| reviews | id, booking_id(UQ,FK), rating, comment | ✗ |
| reports | id, reporter_id(FK), reportable(morph), reason | ✗ |
| audit_logs | id, actor_id(FK), action, auditable(morph) | ✗ |
| system_settings | id, key(UQ), value(JSON), group | ✗ |
| notifications | uuid(PK), notifiable(morph), data(JSON) | ✗ |
