<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuspensionAppealController extends Controller
{
    private function authenticateCredentials(array $data): User
    {
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw DomainException::forbidden('These credentials cannot be used to access an appeal.');
        }
        return $user;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'explanation' => ['required', 'string', 'min:20', 'max:3000'],
        ]);
        $appeal = DB::transaction(function () use ($data, $request) {
            $user = $this->authenticateCredentials($data);
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->status !== UserStatus::Suspended || $user->terminated_at !== null) {
                throw DomainException::conflict('This account has no active suspension to appeal.');
            }
            $suspensionAt = $user->suspended_at ?? $user->updated_at;
            if (SuspensionAppeal::where('user_id', $user->id)
                ->where('suspended_at', $suspensionAt)->where('status', 'pending')->exists()) {
                throw DomainException::conflict('An appeal for this suspension is already under review.');
            }
            $appeal = SuspensionAppeal::create([
                'user_id' => $user->id,
                'suspended_at' => $suspensionAt,
                'explanation' => $data['explanation'],
            ]);
            AuditLogger::record($user, 'suspension.appealed', $appeal, [], $request);
            return $appeal;
        });

        return ApiResponse::created(['id' => $appeal->id, 'status' => 'pending'], 'Appeal submitted.');
    }

    public function mine(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = $this->authenticateCredentials($data);
        return ApiResponse::ok(SuspensionAppeal::where('user_id', $user->id)->latest()->get([
            'id', 'status', 'created_at', 'decided_at', 'decision_rationale',
        ]));
    }

    public function index(Request $request): JsonResponse
    {
        $appeals = SuspensionAppeal::with('user:id,first_name,last_name,email')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest()->paginate(min($request->integer('per_page', 20), 50));
        return ApiResponse::paginated($appeals);
    }

    public function decide(Request $request, SuspensionAppeal $appeal): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'rationale' => ['required', 'string', 'min:10', 'max:3000'],
        ]);
        DB::transaction(function () use ($request, $appeal, $data) {
            $appeal = SuspensionAppeal::whereKey($appeal->id)->lockForUpdate()->firstOrFail();
            if ($appeal->status !== 'pending') throw DomainException::conflict('This appeal has already been decided.');
            $user = User::whereKey($appeal->user_id)->lockForUpdate()->firstOrFail();
            $appeal->forceFill([
                'status' => $data['decision'], 'decided_by' => $request->user()->id,
                'decided_at' => now(), 'decision_rationale' => $data['rationale'],
            ])->save();
            if ($data['decision'] === 'approved' && $user->status === UserStatus::Suspended && $user->terminated_at === null
                && ($user->suspended_at ?? $user->updated_at)?->equalTo($appeal->suspended_at)) {
                $user->forceFill([
                    'status' => UserStatus::Active, 'suspended_until' => null,
                    'suspension_reason' => null, 'suspension_basis' => null,
                ])->save();
            }
            AuditLogger::record($request->user(), 'suspension.appeal_decided', $appeal, $data, $request);
        });
        return ApiResponse::ok(['status' => $data['decision']], 'Appeal decided.');
    }
}
