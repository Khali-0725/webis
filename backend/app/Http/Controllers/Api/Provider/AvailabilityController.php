<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreAvailabilityExceptionRequest;
use App\Http\Requests\Provider\UpdateAvailabilityExceptionRequest;
use App\Http\Requests\Provider\UpdateAvailabilityRulesRequest;
use App\Http\Resources\AvailabilityExceptionResource;
use App\Http\Resources\AvailabilityRuleResource;
use App\Models\ProviderAvailabilityException;
use App\Models\ProviderAvailabilityRule;
use App\Models\Booking;
use App\Exceptions\DomainException;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    public function holidays(Request $request): JsonResponse
    {
        $year = (int) ($request->validate(['year' => ['nullable', 'integer', 'between:2026,2100']])['year'] ?? now('Asia/Manila')->year);
        $profile = $request->user()->providerProfile()->firstOrFail();
        $dates = config('holidays')[$year] ?? [];
        return ApiResponse::ok([
            'exclude_all_holidays' => $profile->exclude_all_holidays,
            'year' => $year,
            'dates' => collect($dates)->map(fn ($name, $date) => ['date' => $date, 'name' => $name])->values(),
            'calendar_available' => count($dates) > 0,
        ]);
    }

    public function updateHolidays(Request $request): JsonResponse
    {
        $data = $request->validate([
            'exclude_all_holidays' => ['required', 'boolean'],
            'confirm_existing_bookings' => ['sometimes', 'boolean'],
        ]);
        $profile = $request->user()->providerProfile()->firstOrFail();
        if ($data['exclude_all_holidays'] && ! $profile->exclude_all_holidays) {
            $dates = array_keys(array_merge(...array_values(config('holidays'))));
            $affected = Booking::where('provider_profile_id', $profile->id)
                ->whereDate('scheduled_date', '>=', now('Asia/Manila')->toDateString())
                ->whereIn(DB::raw('DATE(scheduled_date)'), $dates)->slotBlocking()->count();
            if ($affected > 0 && empty($data['confirm_existing_bookings'])) {
                throw DomainException::conflict("{$affected} future booking(s) fall on excluded holidays. Confirm to keep those bookings while blocking new ones.");
            }
        }
        $profile->forceFill(['exclude_all_holidays' => $data['exclude_all_holidays']])->save();
        return ApiResponse::ok(['exclude_all_holidays' => $profile->exclude_all_holidays], 'Holiday preference updated. Existing bookings remain unchanged.');
    }

    public function rules(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $rules = $profile->availabilityRules()->orderBy('day_of_week')->get();

        return ApiResponse::ok(AvailabilityRuleResource::collection($rules));
    }

    public function updateRules(UpdateAvailabilityRulesRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        DB::transaction(function () use ($profile, $request) {
            $profile->availabilityRules()->delete();

            foreach ($request->validated('rules') as $rule) {
                ProviderAvailabilityRule::create([
                    'provider_profile_id' => $profile->id,
                    'day_of_week' => $rule['day_of_week'],
                    'start_time' => $rule['start_time'],
                    'end_time' => $rule['end_time'],
                    'slot_minutes' => $rule['slot_minutes'],
                    'is_active' => $rule['is_active'] ?? true,
                ]);
            }
        });

        $rules = $profile->availabilityRules()->orderBy('day_of_week')->get();

        return ApiResponse::ok(AvailabilityRuleResource::collection($rules), 'Availability updated successfully.');
    }

    public function exceptions(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $exceptions = $profile->availabilityExceptions()->orderBy('date')->get();

        return ApiResponse::ok(AvailabilityExceptionResource::collection($exceptions));
    }

    public function storeException(StoreAvailabilityExceptionRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        // withTrashed(): UNIQUE(provider_profile_id, date) still holds for a
        // soft-deleted row, so re-adding the same day revives it instead of
        // colliding with it.
        $exception = $profile->availabilityExceptions()
            ->withTrashed()
            ->whereDate('date', $request->validated('date'))
            ->first();

        if ($exception) {
            if ($exception->trashed()) {
                $exception->restore();
            }

            $exception->update($request->validated());
        } else {
            $exception = $profile->availabilityExceptions()->create($request->validated());
        }

        return ApiResponse::created(new AvailabilityExceptionResource($exception), 'Exception saved.');
    }

    public function updateException(UpdateAvailabilityExceptionRequest $request, ProviderAvailabilityException $exception): JsonResponse
    {
        $this->authorize('update', $exception);

        $exception->update($request->validated());

        return ApiResponse::ok(new AvailabilityExceptionResource($exception->fresh()), 'Exception updated.');
    }

    /**
     * Soft delete (the model uses SoftDeletes) - re-adding the same date
     * later revives the row, see storeException().
     */
    public function destroyException(Request $request, ProviderAvailabilityException $exception): JsonResponse
    {
        $this->authorize('delete', $exception);

        $exception->delete();

        return ApiResponse::noContent('Exception removed.');
    }
}
