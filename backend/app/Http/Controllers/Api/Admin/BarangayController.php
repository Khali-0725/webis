<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBarangayRequest;
use App\Http\Requests\Admin\UpdateBarangayRequest;
use App\Http\Resources\BarangayResource;
use App\Models\Barangay;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class BarangayController extends Controller
{
    public function index(): JsonResponse
    {
        $barangays = Barangay::orderBy('name')->get();

        return ApiResponse::ok(BarangayResource::collection($barangays));
    }

    public function store(StoreBarangayRequest $request): JsonResponse
    {
        $barangay = Barangay::create($request->validated());

        return ApiResponse::created(new BarangayResource($barangay), 'Barangay added.');
    }

    public function update(UpdateBarangayRequest $request, Barangay $barangay): JsonResponse
    {
        $barangay->update($request->validated());

        return ApiResponse::ok(new BarangayResource($barangay), 'Barangay updated.');
    }

    /**
     * Never hard-deleted - users, provider profiles, service areas and
     * booking locations all reference a barangay.
     */
    public function toggle(Barangay $barangay): JsonResponse
    {
        $barangay->update(['is_active' => ! $barangay->is_active]);

        return ApiResponse::ok(new BarangayResource($barangay), $barangay->is_active
            ? 'Barangay activated.'
            : 'Barangay deactivated.');
    }
}
