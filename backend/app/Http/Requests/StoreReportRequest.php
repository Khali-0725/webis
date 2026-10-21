<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use App\Enums\UserStatus;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReportRequest extends FormRequest
{
    /**
     * `reportable_type` is an unconstrained polymorphic string column -
     * this allowlist is the only thing stopping a caller from pointing a
     * report at an arbitrary model class.
     */
    private const ALLOWED_TYPES = [
        'user' => User::class,
        'service' => Service::class,
        'provider' => ProviderProfile::class,
        'booking' => Booking::class,
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', Rule::in(array_keys(self::ALLOWED_TYPES))],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', Rule::in(ReportReason::values())],
            'details' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = $this->input('reportable_type');
            $id = (int) $this->input('reportable_id');
            $reporter = $this->user();

            $allowed = match ($type) {
                'booking' => Booking::query()->whereKey($id)
                    ->where(fn ($q) => $q->where('client_id', $reporter->id)
                        ->orWhereHas('providerProfile', fn ($profile) => $profile->where('user_id', $reporter->id)))
                    ->exists(),
                'service' => Service::query()->whereKey($id)->published()->active()
                    ->whereHas('providerProfile', fn ($profile) => $profile->verified()->acceptingBookings()
                        ->whereHas('user', fn ($user) => $user->where('status', UserStatus::Active->value)
                            ->whereNull('terminated_at')))
                    ->exists(),
                'provider' => ProviderProfile::query()->whereKey($id)->verified()->acceptingBookings()
                    ->whereHas('user', fn ($user) => $user->where('status', UserStatus::Active->value)
                        ->whereNull('terminated_at'))
                    ->exists(),
                'user' => $id !== $reporter->id && (
                    User::withTrashed()->whereKey($id)
                        ->whereHas('providerProfile', fn ($profile) => $profile->whereHas('bookings', fn ($booking) => $booking->where('client_id', $reporter->id)))
                        ->exists()
                    || Booking::query()->where('client_id', $id)
                        ->whereHas('providerProfile', fn ($profile) => $profile->where('user_id', $reporter->id))
                        ->exists()
                ),
                default => false,
            };

            if (! $allowed) {
                $validator->errors()->add('reportable_id', 'This record is not available for your report.');
            }

            if ($this->input('reason') === ReportReason::NonPayment->value
                && ($type !== 'booking' || ! $reporter->isProvider())) {
                $validator->errors()->add('reason', 'Non-payment reports require your own provider booking.');
            }
        }];
    }

    public function reportableModelClass(): string
    {
        return self::ALLOWED_TYPES[$this->validated('reportable_type')];
    }
}
