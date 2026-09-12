<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\DomainException;
use App\Models\Booking;
use App\Models\BookingLocation;
use App\Models\BookingStatusHistory;
use App\Models\Payment;
use App\Models\ProviderPaymentMethod;
use App\Models\Service;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a booking. This is the one place R-21 (conflict prevention) is
 * enforced: the overlap check and the insert happen inside the same
 * transaction, with the conflict query taking a row lock, so two concurrent
 * requests for the same slot cannot both succeed.
 */
class BookingService
{
    public function __construct(private readonly SlotGenerator $slots)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $client, array $data): Booking
    {
        return DB::transaction(function () use ($client, $data) {
            $service = Service::query()
                ->published()
                ->active()
                ->whereHas('providerProfile', fn ($q) => $q->verified())
                ->find($data['service_id']);

            if (! $service) {
                throw DomainException::unprocessable('This service is not available for booking.');
            }

            $providerProfileId = $service->provider_profile_id;
            $duration = $service->duration_minutes ?? 60;

            $start = Carbon::parse($data['scheduled_start_time']);
            $end = $start->copy()->addMinutes($duration);

            $this->assertLeadTime($data['scheduled_date'], $start);

            $conflict = Booking::query()
                ->where('provider_profile_id', $providerProfileId)
                ->whereDate('scheduled_date', $data['scheduled_date'])
                ->slotBlocking()
                ->where('scheduled_start_time', '<', $end->format('H:i:s'))
                ->where('scheduled_end_time', '>', $start->format('H:i:s'))
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw DomainException::conflict('This time slot is no longer available.');
            }

            if (! $this->slots->isWithinAvailability($providerProfileId, $data['scheduled_date'], $start->format('H:i:s'), $end->format('H:i:s'))) {
                throw DomainException::unprocessable('The provider is not available at the selected date and time.');
            }

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'client_id' => $client->id,
                'provider_profile_id' => $providerProfileId,
                'service_id' => $service->id,
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_start_time' => $start->format('H:i:s'),
                'scheduled_end_time' => $end->format('H:i:s'),
                'quoted_price' => $service->price,
                'client_notes' => $data['client_notes'] ?? null,
            ]);

            BookingLocation::create([
                'booking_id' => $booking->id,
                'barangay_id' => $data['barangay_id'],
                'address_line' => $data['address_line'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'landmark_notes' => $data['landmark_notes'] ?? null,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => BookingStatus::Pending,
                'changed_by' => $client->id,
            ]);

            // Every booking settles through exactly one Payment row (Phase 7)
            // - created up front, same as DemoDataSeeder assumes, so
            // GET /bookings/{id}/payment never has to branch on "does one
            // exist yet".
            Payment::create([
                'booking_id' => $booking->id,
                'client_id' => $client->id,
                'provider_profile_id' => $providerProfileId,
                'provider_payment_method_id' => ProviderPaymentMethod::where('provider_profile_id', $providerProfileId)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->value('id'),
                'amount' => $service->price,
            ]);

            return $booking->fresh(['service.category', 'providerProfile.user', 'location.barangay']);
        });
    }

    private function assertLeadTime(string $date, Carbon $startTime): void
    {
        $minLeadHours = (int) SystemSetting::getValue('booking.min_lead_hours', 24);
        $maxAdvanceDays = (int) SystemSetting::getValue('booking.max_advance_days', 30);

        $scheduledAt = Carbon::parse($date.' '.$startTime->format('H:i:s'));

        if ($scheduledAt->lt(now()->addHours($minLeadHours))) {
            throw DomainException::unprocessable("Bookings must be made at least {$minLeadHours} hours in advance.");
        }

        if ($scheduledAt->gt(now()->addDays($maxAdvanceDays))) {
            throw DomainException::unprocessable("Bookings cannot be made more than {$maxAdvanceDays} days in advance.");
        }
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'WB-'.Str::upper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
