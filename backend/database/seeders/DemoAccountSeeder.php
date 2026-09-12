<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\Barangay;
use App\Models\ProviderAvailabilityRule;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development and demonstration accounts.
 *
 * Every account created here is clearly labelled as a demo account. These
 * credentials must never be used in a production deployment - the deployment
 * guide instructs the administrator to change them immediately.
 */
class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->account('admin@webis.test', 'System', 'Administrator', UserRole::Admin);
        $this->account('client@webis.test', 'Demo', 'Client', UserRole::Client);
        $provider = $this->account('provider@webis.test', 'Demo', 'Provider', UserRole::Provider);
        $this->account('suspended@webis.test', 'Suspended', 'Account', UserRole::Client, UserStatus::Suspended);

        $this->providerProfile($provider);

        $this->command?->info('Demo accounts seeded. Password for all: password123');
    }

    private function account(
        string $email,
        string $firstName,
        string $lastName,
        UserRole $role,
        UserStatus $status = UserStatus::Active,
    ): User {
        // forceFill, not updateOrCreate: `role` and `status` are guarded on the
        // model by design, so they must be assigned explicitly.
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => Hash::make('password123'),
            'phone' => '09170000000',
            'role' => $role,
            'status' => $status,
            'email_verified_at' => now(),
            'deleted_at' => null,
        ])->save();

        return $user;
    }

    // The demo provider account is a Provider-role User but, unlike factory-made
    // providers, has no ProviderProfile of its own - without this the account
    // 404s on every /provider/* endpoint. Pre-verified so the demo account can
    // exercise the publish flow immediately.
    private function providerProfile(User $user): void
    {
        $profile = ProviderProfile::firstOrNew(['user_id' => $user->id]);
        $isNew = ! $profile->exists;

        if ($isNew) {
            $profile->forceFill([
                'user_id' => $user->id,
                'business_name' => 'Demo Provider Services',
                'bio' => 'Demo provider account for testing the platform.',
                'experience_years' => 5,
                'base_barangay_id' => Barangay::query()->value('id'),
                'latitude' => 14.3,
                'longitude' => 120.85,
                'is_accepting_bookings' => true,
            ])->save();

            $profile->forceFill([
                'verification_status' => VerificationStatus::Approved,
                'verified_at' => now(),
            ])->save();
        }

        // Mon-Sat, 08:00-17:00, same as the factory-seeded demo providers -
        // without this the demo account cannot be booked at all.
        for ($day = 1; $day <= 6; $day++) {
            ProviderAvailabilityRule::firstOrCreate(
                ['provider_profile_id' => $profile->id, 'day_of_week' => $day],
                ['start_time' => '08:00', 'end_time' => '17:00', 'slot_minutes' => 60, 'is_active' => true],
            );
        }
    }
}
