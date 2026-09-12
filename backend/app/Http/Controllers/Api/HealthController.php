<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /api/health
     *
     * Confirms the API boots, the envelope is wired, and MySQL answers.
     * Used as the Phase 1 exit check and as an uptime probe afterwards.
     */
    public function __invoke(): JsonResponse
    {
        $database = 'up';

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (Throwable) {
            // Never surface the DSN, credentials or driver error to the client.
            $database = 'down';
        }

        return ApiResponse::ok([
            'application' => config('app.name'),
            'status' => $database === 'up' ? 'ok' : 'degraded',
            'database' => $database,
            'environment' => config('app.env'),
            'time' => now()->toIso8601String(),
        ]);
    }
}
