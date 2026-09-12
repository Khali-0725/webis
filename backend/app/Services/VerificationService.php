<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;

/**
 * Email-verification business rules.
 *
 * Mirrors Laravel's default signed-URL verification flow, but keeps the
 * lookup and hash comparison out of the controller so it stays testable and
 * consistent with the rest of the domain layer.
 */
class VerificationService
{
    /**
     * Mark the user's email verified.
     *
     * The route already enforces the signature and throttle; this only
     * checks that the hash in the URL actually matches this user's email,
     * which stops one signed link being replayed against a different id.
     *
     * @throws DomainException when the id/hash pair does not match a user
     */
    public function verify(int $id, string $hash): User
    {
        $user = User::find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw DomainException::forbidden('Invalid verification link.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return $user;
    }

    /**
     * Resend the verification link to an already-authenticated, unverified user.
     */
    public function resend(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }
}
