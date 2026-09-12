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

        event(new Registered($user));

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
