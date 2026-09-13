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
            'meta' => array_merge(self::paginationMeta($paginator), $extraMeta),
        ], 200);
    }

    /**
     * @return array<string, int|null>
     */
    public static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * Fully materialises a resource into plain arrays and scalars - the only
     * shape safe to put in the cache. `config('cache.serializable_classes')`
     * is `false` (Laravel 13's default against gadget-chain attacks), so any
     * object that reaches the cache - an Eloquent model, a paginator, a
     * nested JsonResource, an enum - comes back as an incomplete class on
     * the next hit and 500s. `resolve()` alone is not enough: nested
     * resources stay objects inside the resolved array.
     *
     * @return array<int|string, mixed>
     */
    public static function toArray(JsonResource|ResourceCollection $resource): array
    {
        return json_decode(json_encode($resource->resolve(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
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
