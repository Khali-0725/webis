<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\UpdateServiceCategoryRequest;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::ordered()->get();

        return ApiResponse::ok(ServiceCategoryResource::collection($categories));
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
     * Toggle active/inactive. Categories are never hard-deleted - a category
     * with historical services attached must keep resolving for those rows.
     */
    public function toggle(ServiceCategory $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        Cache::forget('public:service-categories');

        return ApiResponse::ok(new ServiceCategoryResource($category), $category->is_active
            ? 'Category activated.'
            : 'Category deactivated.');
    }
}
