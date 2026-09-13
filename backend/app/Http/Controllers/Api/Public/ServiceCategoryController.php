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
        // Cache the materialised array, never the models - see ApiResponse::toArray().
        $categories = Cache::remember('public:service-categories', now()->addMinutes(10), function () {
            return ApiResponse::toArray(ServiceCategoryResource::collection(
                ServiceCategory::active()->ordered()->get(),
            ));
        });

        return ApiResponse::ok($categories);
    }
}
