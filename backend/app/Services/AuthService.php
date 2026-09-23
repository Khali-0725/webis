<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Authentication business rules.
 *
 * Controllers never touch Auth:: directly - everything that decides *who* a
 * user is or *whether* they may sign in lives here, so the rules are testable
 * in isolation and cannot drift between endpoints.
 */
class AuthService
{
    /**
     * Create a client or provider account.
     *
     * The role is re-derived from an allowlist here even though the FormRequest
     * already validated it: defence in depth against a future caller that
     * forgets the request class.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User
    {
        $role = in_array($data['role'] ?? null, UserRole::selfRegisterable(), true)
            ? UserRole::from($data['role'])
            : UserRole::Client;

        $user = DB::transaction(function () use ($data, $role) {
            $user = new User;

            $user->fill([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model cast
                'phone' => $data['phone'] ?? null,
            ]);

            // Never mass-assigned - see User::$fillable.
            $user->role = $role;
            $user->status = UserStatus::Active;

            $user->save();

            if ($role === UserRole::Provider) {
                $user->providerProfile()->create([]);
            }

            return $user;
        });

        // A mail-provider hiccup must never undo a successful registration -
        // the account already exists at this point, and the verification
        // email can always be resent via POST /auth/email/verification-notification.
        try {
            event(new Registered($user));
        } catch (Throwable $e) {
            Log::warning('Registration succeeded but the verification email failed to send.', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return $user;
    }

    /**
     * Start a stateful (cookie) session.
     *
     * @throws DomainException on bad credentials or a suspended account
     */
    public function login(Request $request, string $email, string $password, bool $remember = false): User
    {
        if (! Auth::guard('web')->attempt(['email' => $email, 'password' => $password], $remember)) {
            // Deliberately identical message for "no such user" and "wrong password"
            // so the endpoint cannot be used to enumerate registered emails.
            throw new DomainException('These credentials do not match our records.', 422, [
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return $this->establishSession($request, $user);
    }

    /**
     * Sign in with an already-verified Google identity, creating the account
     * first if this email has never signed up.
     *
     * Google having verified the email is what makes it safe to silently
     * link `$google['sub']` onto a pre-existing password account that shares
     * it, rather than erroring with "email already taken" - it is the same
     * person proving ownership a second, stronger way.
     *
     * @param  array{sub: string, email: string, given_name: string, family_name: string}  $google
     * @param  string|null  $role  Required only to create a *new* account (the
     *                             Register page passes it; the Login page
     *                             does not, since login must never invent a
     *                             role for a stranger).
     * @return array{user: User, created: bool}
     *
     * @throws DomainException on a suspended account, or a login-page attempt
     *                         with no matching account yet
     */
    public function loginWithGoogle(Request $request, array $google, ?string $role = null): array
    {
        $user = User::where('google_id', $google['sub'])
            ->orWhere('email', $google['email'])
            ->first();

        $created = $user === null;

        if ($user === null) {
            // A soft-deleted account still owns the UNIQUE email/google_id,
            // so creating over it would 500 on the constraint. Same outcome
            // as password registration: refuse, restore is an admin action.
            $trashedExists = User::onlyTrashed()
                ->where(fn ($q) => $q->where('google_id', $google['sub'])->orWhere('email', $google['email']))
                ->exists();

            if ($trashedExists) {
                throw new DomainException(
                    'This account has been deleted. Please contact the WEBIS administrator to restore it.',
                    422,
                    ['email' => ['This account has been deleted. Please contact the WEBIS administrator to restore it.']]
                );
            }

            if (! in_array($role, UserRole::selfRegisterable(), true)) {
                throw new DomainException(
                    'No WEBIS account is linked to this Google account yet. Create one from the Register page.',
                    422,
                    ['email' => ['No WEBIS account is linked to this Google account yet. Create one from the Register page.']]
                );
            }

            $user = $this->registerWithGoogle($google, UserRole::from($role));
        } elseif ($user->google_id === null) {
            // First "Continue with Google" on a pre-existing password
            // account - link it rather than blocking on "email taken".
            $user->forceFill(['google_id' => $google['sub']])->saveQuietly();
        }

        // Unlike login(), nothing has authenticated the guard yet - there was
        // no password to Auth::attempt() against.
        Auth::guard('web')->login($user);

        return ['user' => $this->establishSession($request, $user), 'created' => $created];
    }

    /**
     * @param  array{sub: string, email: string, given_name: string, family_name: string}  $google
     */
    private function registerWithGoogle(array $google, UserRole $role): User
    {
        $user = DB::transaction(function () use ($google, $role) {
            $user = new User;

            $user->fill([
                'first_name' => $google['given_name'],
                'last_name' => $google['family_name'],
                'email' => $google['email'],
                // Unknown, unguessable, never shown or emailed - this account
                // only ever signs in through Google unless the owner later
                // sets a real one via "Forgot password".
                'password' => Str::random(40),
            ]);

            $user->google_id = $google['sub'];
            $user->role = $role;
            $user->status = UserStatus::Active;
            // Google already verified this address; asking WEBIS to re-verify
            // an email Google just vouched for would only add friction.
            $user->email_verified_at = now();

            $user->save();

            if ($role === UserRole::Provider) {
                $user->providerProfile()->create([]);
            }

            return $user;
        });

        event(new Registered($user));

        return $user;
    }

    /**
     * Shared tail of every sign-in path: suspension check, session-fixation
     * guard, and the last-login stamp.
     *
     * @throws DomainException when the account is suspended
     */
    private function establishSession(Request $request, User $user): User
    {
        if (! $user->status->canSignIn()) {
            $this->logout($request);

            throw DomainException::forbidden(
                'This account has been suspended. Please contact the WEBIS administrator.'
            );
        }

        // Rotate the session ID to close session-fixation attacks.
        // Token-based (non-stateful) callers have no session to rotate.
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        return $user;
    }

    /**
     * End the session and invalidate the cookie.
     */
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
