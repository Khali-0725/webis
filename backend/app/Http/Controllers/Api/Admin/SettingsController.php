<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    private const KNOWN_KEYS = [
        'platform.name',
        'booking.min_lead_hours',
        'booking.max_advance_days',
    ];

    public function index(): JsonResponse
    {
        $settings = SystemSetting::query()->get(['key', 'value', 'group', 'description']);

        return ApiResponse::ok($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', Rule::in(self::KNOWN_KEYS)],
            'settings.*.value' => ['required'],
        ]);

        foreach ($data['settings'] as $entry) {
            SystemSetting::updateOrCreate(
                ['key' => $entry['key']],
                ['value' => $entry['value'], 'updated_by' => $request->user()->id],
            );
        }

        AuditLogger::record($request->user(), 'settings.updated', null, [
            'keys' => array_column($data['settings'], 'key'),
        ], $request);

        return ApiResponse::ok(
            SystemSetting::query()->get(['key', 'value', 'group', 'description']),
            'Settings updated.'
        );
    }
}
