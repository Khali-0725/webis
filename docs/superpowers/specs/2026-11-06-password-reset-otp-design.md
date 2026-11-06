# Email OTP password reset design

## Intent
Replace the broken email-link password reset with a mobile-friendly six-digit email code. A user requests a code, verifies it, then sets a new password. Mail uses the configured live SMTP sender.

## Approaches
- Chosen: OTP only. One clear flow on desktop and phone; old reset links stop working after deployment.
- Alternative: Keep links and add OTP. Easier migration, but two competing reset paths and more security surface. The user chose OTP only.

## Flow and safeguards
1. Forgot password accepts an email and returns a generic response for unknown addresses. For known accounts, send a random six-digit code. Never include the code in API responses or logs.
2. Store only a hash of the code. It expires after 10 minutes. Allow a new code after 60 seconds. A new code invalidates the previous one.
3. Verify the code with the email. Limit to five failed attempts. A valid code yields a short-lived, one-use reset grant, then the UI shows password fields.
4. Reset requires the grant and a confirmed password. Consume the grant and code atomically, rotate the remember token, and redirect to sign-in.
5. Mail delivery failure returns a service error instead of falsely saying it sent. Old reset links are rejected safely.

## Constraints
Keep the existing API response format and auth page styling. Preserve anti-enumeration messages. Add focused backend tests for expiry, attempts, resend, single use, and successful reset; run frontend checks. No new external service.
