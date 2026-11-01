# Admin action icons and chat language filter

## Intent

Make destructive actions in Admin Users less visually alarming while keeping them clear and safe. Prevent common Tagalog and English profanity from reaching another user through chat.

## Scope and constraints

- In the Users table, replace the red text buttons for Terminate, Delete, and Delete permanently with neutral icon buttons. Keep accessible names and hover titles. Keep the existing confirmation dialogs, reasons, and typed `DELETE` requirement. Use neutral confirmation buttons for these actions; the dialogs retain explicit action text and consequences.
- Add a focused profanity rule to the existing backend chat moderation. Reject both new messages and edits before storage, including `confirm_override` attempts. Return a clear error so the sender can revise the text. Keep the existing violation logging and contact-information rules.
- Cover common strong terms and common spaced/leet variants in Tagalog and English. Use word boundaries to avoid innocent words containing short matching strings. Existing messages are unchanged. No database migration or new dependency.

## Approaches

1. **Chosen: block on the server, reuse current moderation.** Covers send and edit from every client and logs attempts. Smallest change to the current architecture.
2. Mask words on the client before display. Easier to bypass through the API and still stores the original language.

For the admin actions, neutral icon buttons preserve compact table layout. Hiding confirmation text would make irreversible actions less clear, so the dialogs keep words.

## Verification

Test common blocked terms, obfuscations, clean near-matches, send and edit refusal, and accessible action labels. Run relevant backend and frontend checks before deployment.
