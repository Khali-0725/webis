<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Http\Requests\Provider\UpdateProviderServiceAreasRequest;
use App\Http\Requests\Provider\UpdateProviderSkillsRequest;
use App\Http\Resources\ProviderProfileResource;
use App\Models\ProviderServiceArea;
use App\Models\ProviderSkill;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()
            ->with(['baseBarangay', 'skills', 'serviceAreas'])
            ->firstOrFail();

        return ApiResponse::ok(new ProviderProfileResource($profile));
    }

    public function update(UpdateProviderProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $this->authorize('update', $profile);

        $profile->update($request->validated());

        return ApiResponse::ok(new ProviderProfileResource($profile->fresh(['baseBarangay', 'skills', 'serviceAreas'])), 'Profile updated successfully.');
    }

    public function updateSkills(UpdateProviderSkillsRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $this->authorize('update', $profile);

        $skills = collect($request->validated('skills'))
            ->map(fn ($skill) => trim($skill))
            ->filter()
            ->unique(fn ($skill) => mb_strtolower($skill))
            ->values();

        DB::transaction(function () use ($profile, $skills) {
            $profile->skills()->delete();

            foreach ($skills as $skill) {
                ProviderSkill::create(['provider_profile_id' => $profile->id, 'skill' => $skill]);
            }
        });

        return ApiResponse::ok(new ProviderProfileResource($profile->fresh(['baseBarangay', 'skills', 'serviceAreas'])), 'Skills updated successfully.');
    }

    public function updateServiceAreas(UpdateProviderServiceAreasRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $this->authorize('update', $profile);

        $barangayIds = $request->validated('barangay_ids');

        DB::transaction(function () use ($profile, $barangayIds) {
            $profile->serviceAreas()->delete();

            foreach ($barangayIds as $barangayId) {
                ProviderServiceArea::create(['provider_profile_id' => $profile->id, 'barangay_id' => $barangayId]);
            }
        });

        return ApiResponse::ok(new ProviderProfileResource($profile->fresh(['baseBarangay', 'skills', 'serviceAreas'])), 'Service areas updated successfully.');
    }
}
