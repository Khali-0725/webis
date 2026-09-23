<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreWorkExperienceRequest;
use App\Http\Requests\Provider\UpdateWorkExperienceRequest;
use App\Http\Resources\ProviderWorkExperienceResource;
use App\Models\ProviderWorkExperience;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkExperienceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        return ApiResponse::ok(ProviderWorkExperienceResource::collection($profile->workExperiences));
    }

    public function store(StoreWorkExperienceRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();
        $experience = $profile->workExperiences()->create($request->validated());

        return ApiResponse::created(new ProviderWorkExperienceResource($experience), 'Work experience added.');
    }

    public function update(UpdateWorkExperienceRequest $request, ProviderWorkExperience $experience): JsonResponse
    {
        $this->authorize('update', $experience);

        $experience->update($request->validated());

        return ApiResponse::ok(new ProviderWorkExperienceResource($experience->fresh()), 'Work experience updated.');
    }

    public function destroy(Request $request, ProviderWorkExperience $experience): JsonResponse
    {
        $this->authorize('delete', $experience);

        $experience->delete();

        return ApiResponse::noContent('Work experience deleted.');
    }
}
