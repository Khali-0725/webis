<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetOtpService
{
    public function request(string $email): void
    {
        $email = strtolower(trim($email));
        $user = User::where('email', $email)->whereNull('terminated_at')->first();
        if (! $user) return;

        DB::transaction(function () use ($email, $user): void {
            $existing = DB::table('password_reset_challenges')->where('email', $email)->lockForUpdate()->first();
            if ($existing && now()->diffInSeconds($existing->created_at, false) > -60) return;

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            DB::table('password_reset_challenges')->updateOrInsert(['email' => $email], [
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'code_expires_at' => now()->addMinutes(10),
                'failed_attempts' => 0,
                'grant_hash' => null,
                'grant_expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user->notify(new PasswordResetCodeNotification($code));
        });
    }

    public function verify(string $email, string $code): string
    {
        $email = strtolower(trim($email));
        return DB::transaction(function () use ($email, $code): string {
            $challenge = DB::table('password_reset_challenges')->where('email', $email)->lockForUpdate()->first();
            $user = User::where('email', $email)->whereNull('terminated_at')->first();
            if (! $challenge || ! $user || $challenge->user_id !== $user->id
                || now()->greaterThan($challenge->code_expires_at) || $challenge->failed_attempts >= 5 || $challenge->grant_hash) {
                throw DomainException::unprocessable('Invalid or expired code. Request a new one.');
            }

            if (! Hash::check($code, $challenge->code_hash)) {
                DB::table('password_reset_challenges')->where('email', $email)->update([
                    'failed_attempts' => $challenge->failed_attempts + 1,
                    'updated_at' => now(),
                ]);
                return '';
            }

            $grant = bin2hex(random_bytes(32));
            DB::table('password_reset_challenges')->where('email', $email)->update([
                'grant_hash' => hash('sha256', $grant),
                'grant_expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
            ]);
            return $grant;
        }) ?: throw DomainException::unprocessable('Invalid or expired code. Request a new one.');
    }

    public function reset(string $email, string $grant, string $password): void
    {
        $email = strtolower(trim($email));
        DB::transaction(function () use ($email, $grant, $password): void {
            $challenge = DB::table('password_reset_challenges')->where('email', $email)->lockForUpdate()->first();
            $user = User::where('email', $email)->whereNull('terminated_at')->first();
            if (! $challenge || ! $user || $challenge->user_id !== $user->id
                || ! $challenge->grant_hash || ! $challenge->grant_expires_at
                || now()->greaterThan($challenge->grant_expires_at)
                || ! hash_equals($challenge->grant_hash, hash('sha256', $grant))) {
                throw DomainException::unprocessable('Reset session expired. Request a new code.');
            }

            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('password_reset_challenges')->where('email', $email)->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));

            DB::afterCommit(function () use ($user): void {
                try {
                    $user->notify(new PasswordChangedNotification);
                } catch (Throwable) {
                    // A delivery failure must not turn a committed password change into a failed reset.
                    Log::warning('Password change confirmation email delivery failed', ['user_id' => $user->id]);
                }
            });
        });
    }
}
