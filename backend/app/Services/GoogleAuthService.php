<?php

namespace App\Services;

use App\Exceptions\DomainException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies a Google Identity Services credential (an ID token, not an OAuth
 * access token) and returns the claims WEBIS actually needs.
 *
 * Uses Google's tokeninfo endpoint rather than a JWKS/signature library:
 * Google validates the signature, expiry and issuer server-side and simply
 * 400s on anything invalid, which is enough for this app's traffic and adds
 * no new Composer dependency. It only ever runs on the handful of "Continue
 * with Google" clicks per session, not on every request.
 */
class GoogleAuthService
{
    /**
     * @return array{sub: string, email: string, given_name: string, family_name: string}
     *
     * @throws DomainException when the token is missing, expired, forged, or
     *                         minted for a different OAuth client
     */
    public function verify(string $credential): array
    {
        $clientId = config('services.google.client_id');

        if (! $clientId) {
            // Misconfiguration, not a user error - the frontend should never
            // have shown the button without this set.
            Log::error('Google sign-in attempted with no GOOGLE_CLIENT_ID configured.');

            throw DomainException::unprocessable('Google sign-in is not available right now.');
        }

        try {
            $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $credential,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google tokeninfo request failed.', ['exception' => $e->getMessage()]);

            throw DomainException::unprocessable('Could not verify your Google sign-in. Please try again.');
        }

        if ($response->failed()) {
            throw DomainException::unprocessable('Your Google sign-in could not be verified. Please try again.');
        }

        $claims = $response->json();

        // `aud` proves the token was minted for *this* app, not some other
        // site's Google sign-in button being replayed against our API.
        if (($claims['aud'] ?? null) !== $clientId) {
            throw DomainException::unprocessable('This Google sign-in was not issued for WEBIS.');
        }

        // Google's own claim is the string "true", not a boolean - the
        // tokeninfo endpoint returns every value as a string.
        if (($claims['email_verified'] ?? 'false') !== 'true' || empty($claims['email'])) {
            throw DomainException::unprocessable('Your Google account email is not verified.');
        }

        return [
            'sub' => (string) $claims['sub'],
            'email' => mb_strtolower((string) $claims['email']),
            'given_name' => (string) ($claims['given_name'] ?? 'Google'),
            'family_name' => (string) ($claims['family_name'] ?? 'User'),
        ];
    }
}
