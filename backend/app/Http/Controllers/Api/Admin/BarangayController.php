<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBarangayRequest;
use App\Http\Requests\Admin\UpdateBarangayRequest;
use App\Http\Resources\BarangayResource;
use App\Models\Barangay;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TrashFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $barangays = TrashFilter::apply(Barangay::query(), $request)->orderBy('name')->get();

        return ApiResponse::ok(BarangayResource::collection($barangays));
    }

    public function show(Barangay $barangay): JsonResponse
    {
        return ApiResponse::ok(new BarangayResource($barangay));
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
     * The lightweight switch - hides the barangay from pickers without
     * touching anything that already references it.
     */
    public function toggle(Barangay $barangay): JsonResponse
    {
        $barangay->update(['is_active' => ! $barangay->is_active]);

        return ApiResponse::ok(new BarangayResource($barangay), $barangay->is_active
            ? 'Barangay activated.'
            : 'Barangay deactivated.');
    }

    /**
     * Soft delete only - users, provider profiles, service areas and booking
     * locations all reference a barangay, so the row is never physically
     * removed. Refused while anything still points at it: those rows would
     * otherwise render with a blank barangay.
     */
    public function destroy(Request $request, Barangay $barangay): JsonResponse
    {
        $inUse = $barangay->users()->exists()
            || $barangay->providerProfiles()->exists()
            || $barangay->serviceAreas()->exists()
            || $barangay->bookingLocations()->exists();

        if ($inUse) {
            throw DomainException::conflict('This barangay is still in use by users, providers or bookings. Deactivate it instead.');
        }

        $barangay->delete();

        AuditLogger::record($request->user(), 'barangay.deleted', $barangay, [], $request);

        return ApiResponse::noContent('Barangay moved to trash.');
    }

    public function restore(Request $request, Barangay $barangay): JsonResponse
    {
        if (! $barangay->trashed()) {
            throw DomainException::conflict('This barangay is not deleted.');
        }

        $barangay->restore();

        AuditLogger::record($request->user(), 'barangay.restored', $barangay, [], $request);

        return ApiResponse::ok(new BarangayResource($barangay->fresh()), 'Barangay restored.');
    }
}
