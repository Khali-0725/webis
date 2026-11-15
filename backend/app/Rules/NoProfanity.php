<?php

namespace App\Rules;

use App\Services\ChatModerationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoProfanity implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && app(ChatModerationService::class)->containsProfanity($value)) {
            $fail('This name contains language that is not allowed.');
        }
    }
}
