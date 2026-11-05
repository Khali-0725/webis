<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::ok(Faq::query()->where('is_published', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'question', 'answer']));
    }
}
