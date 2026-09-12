<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Password-reset business rules, built on Laravel's password-broker.
 *
 * The controller intentionally returns the same success message whether or
 * not the email exists (see PasswordResetController) - that decision lives
 * there, not here, so this service can report the broker's real status.
 */
class PasswordService
{
    /**
     * Queue a reset-link notification for the given email, if an account
     * exists. Silently no-ops otherwise - the caller must not use the
     * return value to infer whether the account exists.
     */
    public function sendResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset the password for the account matching the given credentials/token.
     *
     * @param  array{email: string, password: string, token: string}  $credentials
     *
     * @throws DomainException when the token is invalid or expired
     */
    public function reset(array $credentials): void
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw DomainException::unprocessable(__($status), [
                'email' => [__($status)],
            ]);
        }
    }
}
