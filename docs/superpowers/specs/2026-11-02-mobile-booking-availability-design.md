# Mobile booking and availability design

## Intent

Make WEBIS usable at phone width without horizontal page/table scrolling, while applying the professor's requested booking navigation, compact action icons, suspension appeal visibility, and simpler provider availability.

## Scope and choices

- On booking detail pages, the shell's back link goes to the role's booking list and reads "Back to booking". The booking list does not link to itself. Other pages retain their dashboard link.
- Managed tables become labeled, stacked record cards on narrow screens. The existing table stays on wider screens. Record actions use compact neutral icons with accessible names and hover titles; confirmation dialogs retain words.
- The login page only offers "Appeal an account suspension" after an authenticated sign-in attempt is refused because the account is suspended. Wrong credentials and terminated accounts do not show it. The appeal API continues to enforce suspended status.
- Provider availability is one page module with weekly days and time windows, unavailable dates/custom hours, and holiday exclusions. Existing APIs and stored exceptions remain intact. The client booking date/time selector continues to show only server-approved slots and gives a clear message when none remain.
- "Bottom stock" is deferred at the user's request. No new bottom navigation is introduced.

## Approaches considered

- Rebuild every table as separate mobile markup: precise, but duplicates all record rendering and actions.
- Use a shared responsive table shell that reads column headers for mobile card labels: one consistent layout and no duplicate actions. Chosen for the existing table-heavy app.
- Force horizontal scrolling or scale down table text: rejected because the professor requires phone-width usability.

## Constraints

- Preserve API shape, existing bookings, exception records, and desktop behavior.
- Mobile cards must remain keyboard and screen-reader usable.
- No new dependency or database migration.
