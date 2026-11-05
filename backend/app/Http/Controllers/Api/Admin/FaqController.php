<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Http\Resources\Admin\FaqResource;
use App\Models\Faq;
use App\Support\Api\ApiResponse;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireSuperAdmin($request);

        return ApiResponse::ok(FaqResource::collection(Faq::query()->orderBy('sort_order')->orderBy('id')->get()));
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = Faq::create($request->validated());
        AuditLogger::record($request->user(), 'faq.created', $faq, [], $request);

        return ApiResponse::created(new FaqResource($faq), 'FAQ created.');
    }

    public function update(UpdateFaqRequest $request, Faq $faq): JsonResponse
    {
        $faq->update($request->validated());
        AuditLogger::record($request->user(), 'faq.updated', $faq, ['fields' => array_keys($request->validated())], $request);

        return ApiResponse::ok(new FaqResource($faq->fresh()), 'FAQ updated.');
    }

    public function destroy(Request $request, Faq $faq): JsonResponse
    {
        $this->requireSuperAdmin($request);
        $faq->delete();
        AuditLogger::record($request->user(), 'faq.deleted', $faq, [], $request);

        return ApiResponse::noContent('FAQ moved to archive.');
    }

    private function requireSuperAdmin(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            throw DomainException::forbidden('Only a Super Administrator may manage FAQs.');
        }
    }
}
