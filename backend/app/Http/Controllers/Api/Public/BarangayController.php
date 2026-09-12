<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\BarangayResource;
use App\Models\Barangay;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class BarangayController extends Controller
{
    public function index(): JsonResponse
    {
        $barangays = Barangay::active()->orderBy('name')->get();

        return ApiResponse::ok(BarangayResource::collection($barangays));
    }
}
