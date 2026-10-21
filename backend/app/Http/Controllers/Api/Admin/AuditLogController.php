<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'action' => ['sometimes', 'string'],
            'auditable_type' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'user_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $logs = AuditLog::query()
            ->with('actor')
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', "%{$action}%"))
            ->when($filters['auditable_type'] ?? null, fn ($query, $type) => $query->where('auditable_type', 'like', "%{$type}%"))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['user_id'] ?? null, fn ($query, $id) => $query
                ->where('auditable_type', User::class)->where('auditable_id', $id))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($logs, AuditLogResource::class);
    }
}
