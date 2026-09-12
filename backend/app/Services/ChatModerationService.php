<?php

namespace App\Services;

use App\Enums\ViolationCategory;
use App\Enums\ViolationSeverity;
use Normalizer;

/**
 * Deterministic chat moderation filter - no AI, per the thesis's own scope
 * decision (docs/PHASE-0-REQUIREMENTS-AUDIT.md §9.3). Pure and HTTP-free so
 * it can be unit tested against a corpus without touching the database.
 *
 * Rules live in config/chat.php, not here, so they can be tuned without a
 * code change.
 */
class ChatModerationService
{
    /**
     * @return array{tier: 'blocked'|'warned'|'allowed', category: ?ViolationCategory, matched_rule: ?string, severity: ?ViolationSeverity}
     */
    public function evaluate(string $body): array
    {
        $variants = $this->buildVariants($body);

        foreach (config('chat.block_rules', []) as $rule) {
            if ($this->matches($rule, $variants)) {
                return [
                    'tier' => 'blocked',
                    'category' => ViolationCategory::from($rule['category']),
                    'matched_rule' => $rule['rule'],
                    'severity' => ViolationSeverity::from($rule['severity'] ?? 'high'),
                ];
            }
        }

        $guardedVariants = $this->applyFalsePositiveGuards($variants);

        foreach (config('chat.warn_rules', []) as $rule) {
            if ($this->matches($rule, $guardedVariants)) {
                return [
                    'tier' => 'warned',
                    'category' => ViolationCategory::from($rule['category']),
                    'matched_rule' => $rule['rule'],
                    'severity' => ViolationSeverity::Low,
                ];
            }
        }

        return ['tier' => 'allowed', 'category' => null, 'matched_rule' => null, 'severity' => null];
    }

    /**
     * Builds the strings every rule is checked against.
     *
     * `digits`/`digitsDespaced` keep the original digits intact (needed for
     * phone-number-shaped patterns - leetspeak substitution would corrupt a
     * real digit sequence). `text`/`textDespaced` run the leetspeak
     * substitution so a word obfuscated as "f4c3book" or spaced out as
     * "f a c e b o o k" still matches a plain-word rule.
     *
     * @return array{digits: string, digitsDespaced: string, text: string, textDespaced: string}
     */
    private function buildVariants(string $body): array
    {
        $digits = $this->baseNormalize($body);
        $text = strtr($digits, [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's',
        ]);

        return [
            'digits' => $digits,
            'digitsDespaced' => preg_replace('/[^a-z0-9]/', '', $digits) ?? '',
            'text' => $text,
            'textDespaced' => preg_replace('/[^a-z0-9]/', '', $text) ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array{digits: string, digitsDespaced: string, text: string, textDespaced: string}  $variants
     */
    private function matches(array $rule, array $variants): bool
    {
        // Digit-shaped signals (a phone number, a bare digit run) are only
        // ever checked against the digit-preserving variants - leetspeak
        // substitution would turn "09171234567" into letters and never match.
        $haystacks = ($rule['category'] === 'phone_number')
            ? [$variants['digits'], $variants['digitsDespaced']]
            : [$variants['text'], $variants['textDespaced'], $variants['digits']];

        foreach ($haystacks as $haystack) {
            if (isset($rule['pattern']) && preg_match($rule['pattern'], $haystack) === 1) {
                if (! $this->isAllowlisted($rule, $haystack)) {
                    return true;
                }

                continue;
            }

            if (isset($rule['phrases'])) {
                foreach ($rule['phrases'] as $phrase) {
                    if (str_contains($haystack, $phrase)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function isAllowlisted(array $rule, string $haystack): bool
    {
        foreach ($rule['allowlist'] ?? [] as $safeDomain) {
            if (str_contains($haystack, strtolower($safeDomain))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strips every false-positive guard pattern (a peso amount, a time of
     * day) out of the digit-preserving variants *before* the warn rules run,
     * so a digit run that is really "₱1,500,000" or "9:00am" never trips the
     * bare-digit-run rule in the first place - a whole-message check run
     * *after* matching would still flag a real sentence that merely contains
     * one of these alongside other words.
     *
     * A short 1-4 digit number (e.g. a house number) is only ever a
     * false positive when it is the *entire* message - it is short enough
     * that it can't trigger the 7+-digit bare-digit-run rule anyway, so no
     * special handling is needed here beyond that rule's own length bound.
     *
     * @param  array{digits: string, digitsDespaced: string, text: string, textDespaced: string}  $variants
     * @return array{digits: string, digitsDespaced: string, text: string, textDespaced: string}
     */
    private function applyFalsePositiveGuards(array $variants): array
    {
        $guards = config('chat.false_positive_guards', []);
        $strip = function (string $value) use ($guards): string {
            foreach ($guards as $key => $pattern) {
                if ($key === 'short_number') {
                    continue; // whole-message-only guard, not a substring strip.
                }
                $value = preg_replace($pattern, ' ', $value) ?? $value;
            }

            return $value;
        };

        return [
            'digits' => $strip($variants['digits']),
            'digitsDespaced' => preg_replace('/[^a-z0-9]/', '', $strip($variants['digits'])) ?? '',
            'text' => $variants['text'],
            'textDespaced' => $variants['textDespaced'],
        ];
    }

    /**
     * Lowercase; strip zero-width characters and combining marks (accents);
     * collapse whitespace. Digits are left untouched.
     */
    private function baseNormalize(string $body): string
    {
        $value = mb_strtolower($body);

        if (class_exists(Normalizer::class)) {
            $decomposed = Normalizer::normalize($value, Normalizer::FORM_D) ?: $value;
            $value = preg_replace('/\p{Mn}/u', '', $decomposed) ?? $value;
        }

        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
