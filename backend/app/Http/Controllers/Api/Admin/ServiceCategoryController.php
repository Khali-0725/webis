<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\UpdateServiceCategoryRequest;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = TrashFilter::apply(ServiceCategory::query(), $request)->ordered()->get();

        return ApiResponse::ok(ServiceCategoryResource::collection($categories));
    }

    public function show(ServiceCategory $category): JsonResponse
    {
        return ApiResponse::ok(new ServiceCategoryResource($category));
    }

    public function store(StoreServiceCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = ServiceCategory::create($data);

        Cache::forget('public:service-categories');

        return ApiResponse::created(new ServiceCategoryResource($category), 'Category created successfully.');
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $category): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('name', $data) && ! array_key_exists('slug', $data)) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        Cache::forget('public:service-categories');

        return ApiResponse::ok(new ServiceCategoryResource($category), 'Category updated successfully.');
    }

    /**
     * Toggle active/inactive - the lightweight "hide from the public"
     * switch. Delete (below) is the stronger one: it removes the category
     * from every listing, including this admin one, until restored.
     */
    public function toggle(ServiceCategory $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        Cache::forget('public:service-categories');

        return ApiResponse::ok(new ServiceCategoryResource($category), $category->is_active
            ? 'Category activated.'
            : 'Category deactivated.');
    }

    /**
     * Soft delete only - a category with historical services attached must
     * keep resolving for those rows, so the row is never physically removed.
     */
    public function destroy(Request $request, ServiceCategory $category): JsonResponse
    {
        if ($category->services()->exists()) {
            throw DomainException::conflict('This category still has services under it. Move or delete those first, or deactivate the category instead.');
        }

        $category->delete();

        Cache::forget('public:service-categories');

        AuditLogger::record($request->user(), 'category.deleted', $category, [], $request);

        return ApiResponse::noContent('Category moved to trash.');
    }

    public function restore(Request $request, ServiceCategory $category): JsonResponse
    {
        if (! $category->trashed()) {
            throw DomainException::conflict('This category is not deleted.');
        }

        $category->restore();

        Cache::forget('public:service-categories');

        AuditLogger::record($request->user(), 'category.restored', $category, [], $request);

        return ApiResponse::ok(new ServiceCategoryResource($category->fresh()), 'Category restored.');
    }
}
