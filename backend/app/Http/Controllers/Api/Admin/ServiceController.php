<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TrashFilter::apply(Service::query(), $request)->with(['category', 'providerProfile.user']);

        if ($categoryId = $request->integer('service_category_id')) {
            $query->where('service_category_id', $categoryId);
        }

        if ($providerProfileId = $request->integer('provider_profile_id')) {
            $query->where('provider_profile_id', $providerProfileId);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        return ApiResponse::paginated($query->orderByDesc('created_at')->paginate($perPage), ServiceResource::class);
    }

    public function show(Service $service): JsonResponse
    {
        return ApiResponse::ok(new ServiceResource($service->load(['category', 'providerProfile.user'])));
    }

    /**
     * Admin moderation edit of a provider's listing (fix a title, move it to
     * the right category, correct a price). Audited, since the admin is
     * editing content they don't own.
     */
    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        AuditLogger::record($request->user(), 'service.updated', $service, ['fields' => array_keys($request->validated())], $request);

        return ApiResponse::ok(new ServiceResource($service->fresh(['category', 'providerProfile.user'])), 'Service updated.');
    }

    /**
     * Admin can deactivate any service directly - not routed through the
     * owner-only ServicePolicy, since this route is already role:admin-gated
     * and is a distinct admin capability from the provider's own toggle.
     */
    public function toggle(Service $service): JsonResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        return ApiResponse::ok(new ServiceResource($service->fresh('category')), $service->is_active
            ? 'Service activated.'
            : 'Service deactivated.');
    }

    /**
     * Soft delete - bookings keep referencing the row (Booking::service()
     * resolves trashed services), the listing just stops appearing anywhere.
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        $service->delete();

        AuditLogger::record($request->user(), 'service.deleted', $service, [], $request);

        return ApiResponse::noContent('Service moved to trash.');
    }

    public function restore(Request $request, Service $service): JsonResponse
    {
        if (! $service->trashed()) {
            throw DomainException::conflict('This service is not deleted.');
        }

        $service->restore();

        AuditLogger::record($request->user(), 'service.restored', $service, [], $request);

        return ApiResponse::ok(new ServiceResource($service->fresh(['category', 'providerProfile.user'])), 'Service restored.');
    }
}
