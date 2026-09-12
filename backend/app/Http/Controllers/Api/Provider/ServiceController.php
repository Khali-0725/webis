<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreServiceRequest;
use App\Http\Requests\Provider\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Api\ApiResponse;
use App\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        $services = $profile->services()
            ->with('category')
            ->latest()
            ->paginate(config('webis.pagination.default'));

        return ApiResponse::paginated($services, ServiceResource::class);
    }

    public function show(Service $service): JsonResponse
    {
        $this->authorize('update', $service);

        return ApiResponse::ok(new ServiceResource($service->load('category')));
    }

    /**
     * Per the API spec ("store/publish blocked unless verification_status=approved"),
     * an unverified provider cannot even draft a service - not only publish
     * one. Checked here, not just at publish(), so the block is not
     * bypassable by creating a service and leaving it unpublished forever.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $profile = $request->user()->providerProfile()->firstOrFail();

        if (! $profile->isVerified()) {
            throw DomainException::forbidden('Your provider account must be verified before you can add a service.');
        }

        $service = $profile->services()->create($request->validated());

        return ApiResponse::created(new ServiceResource($service->load('category')), 'Service created successfully.');
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $this->authorize('update', $service);

        $service->update($request->validated());

        return ApiResponse::ok(new ServiceResource($service->fresh('category')), 'Service updated successfully.');
    }

    /**
     * Enforces the one real business rule in this controller: only a provider
     * whose verification has been approved by an admin may publish a service.
     */
    public function publish(Request $request, Service $service): JsonResponse
    {
        $this->authorize('update', $service);

        if (! $service->providerProfile->isVerified()) {
            throw DomainException::forbidden('Your provider account must be verified before you can publish a service.');
        }

        $service->forceFill(['published_at' => now(), 'is_active' => true])->save();

        return ApiResponse::ok(new ServiceResource($service->fresh('category')), 'Service published successfully.');
    }

    public function deactivate(Request $request, Service $service): JsonResponse
    {
        $this->authorize('update', $service);

        $service->update(['is_active' => false]);

        return ApiResponse::ok(new ServiceResource($service->fresh('category')), 'Service deactivated successfully.');
    }
}
