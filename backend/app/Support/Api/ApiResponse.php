<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Single source of truth for the WEBIS API response envelope.
 *
 * Success: { "success": true,  "data": ..., "meta": {...}? , "message": "..."? }
 * Failure: { "success": false, "message": "...", "errors": {...} }
 *
 * Controllers must never build a raw response array by hand.
 */
final class ApiResponse
{
    /**
     * A successful response carrying a payload.
     *
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function ok($data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => self::normalize($data),
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * A successful response for a newly created resource.
     *
     * @param  mixed  $data
     */
    public static function created($data = null, ?string $message = null): JsonResponse
    {
        return self::ok($data, $message ?? 'Created successfully.', [], 201);
    }

    /**
     * A successful response with no body.
     */
    public static function noContent(?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => $message ?? 'Done.',
        ], 200);
    }

    /**
     * A paginated collection response. Pagination lives in `meta`, never inside `data`.
     */
    public static function paginated(LengthAwarePaginator $paginator, ?string $resourceClass = null, array $extraMeta = []): JsonResponse
    {
        $items = $paginator->getCollection();

        $data = $resourceClass !== null
            ? $resourceClass::collection($items)->resolve()
            : $items->toArray();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ], $extraMeta),
        ], 200);
    }

    /**
     * A failure response.
     *
     * @param  array<string, array<int, string>>  $errors
     */
    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }

    /**
     * Unwraps API Resources so the envelope is never double-wrapped in `data`.
     *
     * @param  mixed  $data
     * @return mixed
     */
    private static function normalize($data)
    {
        if ($data instanceof ResourceCollection || $data instanceof JsonResource) {
            return $data->resolve();
        }

        return $data;
    }
}
