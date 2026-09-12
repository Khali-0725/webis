<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chat moderation
    |--------------------------------------------------------------------------
    |
    | Deterministic, regex/phrase-based rules (no AI) per the thesis's own
    | scope decision - see docs/PHASE-0-REQUIREMENTS-AUDIT.md §9.3. Kept in
    | one config array so the rules are tunable without touching
    | App\Services\ChatModerationService.
    |
    | Every pattern is matched against BOTH the normalized body and a fully
    | "despaced" copy (all non-alphanumerics removed), which is what catches
    | "f a c e b o o k" or "0917.123.4567".
    |
    */

    // A confirmed contact detail or an explicit bypass phrase. The message
    // is never stored; a `chat_violations` row is written instead.
    'block_rules' => [
        [
            'category' => 'phone_number',
            'rule' => 'ph_mobile_number',
            'pattern' => '/(\+?63|0)9\d{9}/',
            'severity' => 'high',
        ],
        [
            'category' => 'email',
            'rule' => 'email_address',
            'pattern' => '/[a-z0-9._%+-]+\s*(@|\(at\)|\[at\]|\bat\b)\s*[a-z0-9.-]+\s*(\.|\(dot\)|\[dot\]|\bdot\b)\s*[a-z]{2,}/i',
            'severity' => 'high',
        ],
        [
            'category' => 'social_media',
            'rule' => 'social_platform_mention',
            'pattern' => '/\b(facebook|fb|messenger|m\.?me|instagram|ig|viber|whatsapp|wa\.?me|telegram|t\.?me|tiktok|discord)\b/i',
            'severity' => 'medium',
        ],
        [
            'category' => 'off_platform_transaction',
            'rule' => 'bypass_phrase',
            'phrases' => [
                'contact me outside', 'message me on', 'call me', 'text me',
                'add me on', 'pay me directly', 'send payment directly',
                "don't book here", 'do not book here', 'outside the app',
                'labas na lang', 'direct na lang', 'gcash ko',
            ],
            'severity' => 'high',
        ],
        [
            'category' => 'external_url',
            'rule' => 'domain_like_link',
            'pattern' => '/\b[a-z0-9-]+\.(com|net|ph|io|co|me)\b/i',
            'allowlist' => ['webis.test', 'webis.ph', 'webis.com'],
            'severity' => 'medium',
        ],
    ],

    // A weak signal that could be innocent (a price, a house number) but is
    // worth a "send anyway?" confirmation.
    'warn_rules' => [
        [
            'category' => 'phone_number',
            'rule' => 'bare_digit_run',
            'pattern' => '/\b\d{7,}\b/',
        ],
        [
            'category' => 'other',
            'rule' => 'call_keyword',
            'pattern' => '/\bcall\b/i',
        ],
    ],

    // Suppresses a would-be warn when the ONLY thing matched is one of these
    // - never a violation on its own, per §9.3's false-positive guards.
    'false_positive_guards' => [
        'peso_amount' => '/₱\s?\d+(\.\d{1,2})?/',
        'time_of_day' => '/\b\d{1,2}:\d{2}\s?(am|pm)?\b/i',
        'short_number' => '/^\d{1,4}$/',
    ],

    // "3+ warnings or 2+ blocks inside 24h escalates the user into the admin
    // queue" - forces the NEXT message to `flagged` regardless of its own
    // content once either threshold is crossed.
    'escalation' => [
        'window_hours' => 24,
        'warn_threshold' => 3,
        'block_threshold' => 2,
    ],
];
