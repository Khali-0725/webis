<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ServiceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Cache::remember('public:service-categories', now()->addMinutes(10), function () {
            return ServiceCategory::active()->ordered()->get();
        });

        return ApiResponse::ok(ServiceCategoryResource::collection($categories));
    }
}
