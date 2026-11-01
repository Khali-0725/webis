# Neutral actions and booking pin range

## User goal

Remove red action buttons throughout WEBIS, especially Archive. Use compact icons in lists and toolbars. Prevent clients from creating a booking with a map pin far from the barangay selected in the address form. The selected barangay, rather than device GPS, is the location authority.

## UI design

- Use neutral outline icon buttons with visible hover titles and accessible action names for compact row and bulk actions: delete, archive, reject, suspend, and similar actions. Preserve current confirmation dialogs and their written action names so consequential actions remain clear.
- Make confirmation, form, and sidebar buttons navy or neutral, with no red button styling. Keep red validation text and error alerts because they are not buttons.
- Reuse one icon button component and the existing icon set rather than adding an icon dependency. Keep loading and disabled behavior.

## Location design

- The existing barangay coordinates are approximate center points, not surveyed boundaries. Exact barangay containment cannot be guaranteed with the available data.
- As an immediate safeguard, allow a booking pin only within **2 km** of the selected barangay's center. If the selected barangay has no coordinates, prevent booking until an administrator sets them.
- Show the allowed radius on the map. Reject an out-of-range map click or device-location result with a clear message. Changing province, municipality, or barangay clears the pin and confirmation and recenters the map.
- Enforce the same radius in the booking API, regardless of frontend behavior. A manipulated request with a China pin must receive a validation error and create no booking.
- Continue requiring the client to confirm the address and pin. Do not require GPS, because the user chose the selected barangay as the authority.

## Verification

- Exercise representative list, archive, and confirmation controls by test; scan the frontend for any red button styling.
- Test nearby and remote coordinates through the booking API, including missing center coordinates. Test the map's pin guard and barangay switching.
- Run frontend and backend suites, lint, build, then push to `master` and verify Vercel and Render deployments.

## Limitation

The 2 km rule blocks distant pins but cannot prove that every accepted point lies within the exact barangay boundary. Accurate boundaries would require verified polygon data for every supported barangay.
