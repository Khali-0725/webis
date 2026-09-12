<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ProviderAvailabilityException;
use App\Models\ProviderAvailabilityRule;
use Carbon\Carbon;

/**
 * Computes free booking slots for a provider on a single date, and checks
 * whether an arbitrary [start, end) window is inside their availability.
 *
 * Pure read logic, no HTTP - kept separate so it is unit-testable on its own
 * and reusable by both the public "free slots" endpoint and BookingService's
 * availability check at creation time.
 */
class SlotGenerator
{
    /**
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public function freeSlots(int $providerProfileId, string $date): array
    {
        $day = Carbon::parse($date);
        $exception = $this->exceptionFor($providerProfileId, $day);

        if ($exception?->is_closed) {
            return [];
        }

        $rule = $this->ruleFor($providerProfileId, $day);

        if (! $rule) {
            return [];
        }

        $windowStart = Carbon::parse($exception?->start_time ?: $rule->start_time);
        $windowEnd = Carbon::parse($exception?->end_time ?: $rule->end_time);

        $booked = Booking::query()
            ->where('provider_profile_id', $providerProfileId)
            ->whereDate('scheduled_date', $day->toDateString())
            ->slotBlocking()
            ->get(['scheduled_start_time', 'scheduled_end_time']);

        $slots = [];
        $cursor = $windowStart->copy();

        while ($cursor->copy()->addMinutes($rule->slot_minutes)->lte($windowEnd)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($rule->slot_minutes);

            $overlaps = $booked->contains(function ($booking) use ($slotStart, $slotEnd) {
                $bookedStart = Carbon::parse($booking->scheduled_start_time);
                $bookedEnd = Carbon::parse($booking->scheduled_end_time);

                return $slotStart->lt($bookedEnd) && $slotEnd->gt($bookedStart);
            });

            if (! $overlaps) {
                $slots[] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                ];
            }

            $cursor->addMinutes($rule->slot_minutes);
        }

        return $slots;
    }

    /**
     * Whether the given [startTime, endTime) window on $date is inside the
     * provider's recurring rule and not blocked by an exception. Does not
     * check for booking conflicts - that is a separate, lock-guarded step.
     */
    public function isWithinAvailability(int $providerProfileId, string $date, string $startTime, string $endTime): bool
    {
        $day = Carbon::parse($date);
        $exception = $this->exceptionFor($providerProfileId, $day);

        if ($exception?->is_closed) {
            return false;
        }

        $rule = $this->ruleFor($providerProfileId, $day);

        if (! $rule) {
            return false;
        }

        $windowStart = Carbon::parse($exception?->start_time ?: $rule->start_time);
        $windowEnd = Carbon::parse($exception?->end_time ?: $rule->end_time);

        return Carbon::parse($startTime)->gte($windowStart) && Carbon::parse($endTime)->lte($windowEnd);
    }

    private function exceptionFor(int $providerProfileId, Carbon $day): ?ProviderAvailabilityException
    {
        return ProviderAvailabilityException::query()
            ->where('provider_profile_id', $providerProfileId)
            ->whereDate('date', $day->toDateString())
            ->first();
    }

    private function ruleFor(int $providerProfileId, Carbon $day): ?ProviderAvailabilityRule
    {
        return ProviderAvailabilityRule::query()
            ->where('provider_profile_id', $providerProfileId)
            ->where('day_of_week', $day->dayOfWeek)
            ->where('is_active', true)
            ->first();
    }
}
