# Professor revision checklist — 8 November 2026

Scope: the user's written feedback, screenshots, and `REVISIONS.docx`.
Existing design, account permissions, historical records, and the working Gmail
OTP delivery are preserved.

| Feedback | Result |
| --- | --- |
| Sidebar sections from People to System | Already deployed before this revision: independent collapsible groups, current section opens automatically. |
| Too many dashboard quick links; add icons | Four shortcuts: users, verification, bookings, reports. Each uses the existing icon set. |
| Barangay “Name” terminology | “Barangay name” in create/edit fields and the table heading. |
| Bulk selection/action placement | Checkbox, selected count, and archive icon align together; archive remains disabled with no selection. |
| User filters on one line | Search, role, status, sort, and date filters share a compact desktop toolbar; controls wrap/stack on smaller screens. |
| Profile picture preview | Saved photo, or initials when no photo exists, appears at the top of My Profile with a link to the existing upload control. |
| Remove provider system status | Technical status card removed from Provider Dashboard; admin monitoring retained. |
| Oversized verified cards | Provider verification appears inline beside meaningful dashboard/profile titles; pending document controls remain available. |
| Consistent row actions | Service edit, publish/activate, deactivate, restore, delete and CMS FAQ row actions use the existing accessible icon controls. |
| Activate an inactive business/service | Previously published inactive services expose Activate. Reactivation preserves the original publication date. |
| Inactive item still says Published | Inactive published services show “Publication paused” and “Inactive”; existing public visibility and business availability checks remain enforced. |
| Search bookings by client | Provider and admin booking lists search full client names or booking references before pagination. Changing search resets page. |
| Actions for flagged messages | Existing dismiss/warn/suspend controls retained. Warning now notifies the account. Account-management authorization also applies to warn/suspend, preventing ordinary admins from sanctioning administrator accounts. |
| Reviews distinct per transaction | Each provider review shows a booking reference linked to its booking, the service, and the scheduled date. |
| Provider/client welcome text | Both dashboards welcome the signed-in user by first name. |
| Redundant landing page buttons | Header offers Login, plus dashboard access for signed-in users. Removed header Register, hero Sign in, and lower Register as a provider. Kept one Create an account CTA. |
| Home beside About, left aligned | Home, About and Contact sit beside the brand; authentication control stays on the right. Mobile menu follows the same destinations. |
| Add landing page picture | Added a generated illustrative home-service photograph, stored with the project and displayed on desktop and mobile. |
| Resolved report appears under review/disappears | Reports now default to All statuses. The API already rejects changes from a closed report; added a regression proving stale reviewing actions cannot reopen resolved reports. Historical notifications are distinguished from current report status. |
| Admin must verify providers | Final Verify provider action is exposed with guidance. Required current document approvals remain mandatory; no verification bypass was added. |
| Admin should not have earnings | Admin totals/chart now read “Verified Payment Volume,” accurately describing verified platform payments rather than administrator income. Actual provider earnings remain unchanged. |

## Findings that explain the reported behavior

Reports were disappearing because the default API query returned only open cases.
Resolve already sends `resolved`, while Acknowledge sends `reviewing`. Closed
reports are locked against stale updates. No resolved-to-reviewing regression was
reproduced; tests now explicitly cover this complaint for a super administrator.

Provider verification has two stages: approve the current required documents,
then approve the provider. Missing or unapproved required documents still block
the final decision. The interface now makes the final action and prerequisite
clearer.

## Validation

- Backend: 415 tests passed, 1,402 assertions.
- Frontend: 92 tests passed across 33 files.
- Frontend ESLint and production build passed.
- Scoped UI detector reported no findings.
- New regression coverage includes booking search, service reactivation and
  public inactivity filtering, report finality/listing, review transaction
  context, moderation authorization and warning delivery, dashboard layout
  content, public navigation, profile photo display, and bulk selection.

No database migration or additional paid service is required.
