<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['category', 'providerProfile.user']);

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
}
