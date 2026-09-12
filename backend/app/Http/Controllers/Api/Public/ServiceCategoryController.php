<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::active()->ordered()->get();

        return ApiResponse::ok(ServiceCategoryResource::collection($categories));
    }
}
