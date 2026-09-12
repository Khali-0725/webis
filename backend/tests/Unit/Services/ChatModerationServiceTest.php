<?php

namespace Tests\Unit\Services;

use App\Services\ChatModerationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChatModerationServiceTest extends TestCase
{
    private ChatModerationService $moderation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moderation = new ChatModerationService();
    }

    #[DataProvider('blockedCorpus')]
    public function test_it_blocks_confirmed_contact_details_and_bypass_phrases(string $body): void
    {
        $result = $this->moderation->evaluate($body);

        $this->assertSame('blocked', $result['tier'], "Expected \"$body\" to be blocked.");
    }

    public static function blockedCorpus(): array
    {
        return [
            'PH mobile number' => ['You can reach me at 09171234567 instead'],
            'PH mobile number with country code' => ['+639171234567 is my number'],
            'spaced-out facebook' => ['add me on f a c e b o o k'],
            'leetspeak facebook' => ['message me on f4c3book'],
            'plain email' => ['email me at juan.delacruz@example.com'],
            'obfuscated email' => ['juan.delacruz (at) example (dot) com'],
            'bypass phrase - call me' => ['just call me, no need to book here'],
            'bypass phrase - outside the app' => ["let's talk outside the app"],
            'bypass phrase - tagalog' => ['bayad na lang labas na lang, mas mura'],
            'external url' => ['check out my portfolio at myservice.com'],
        ];
    }

    #[DataProvider('warnedCorpus')]
    public function test_it_warns_on_weak_signals(string $body): void
    {
        $result = $this->moderation->evaluate($body);

        $this->assertSame('warned', $result['tier'], "Expected \"$body\" to be warned.");
    }

    public static function warnedCorpus(): array
    {
        return [
            'bare long digit run' => ['my number is 1234567'],
            'the word call alone' => ['you can call anytime'],
        ];
    }

    #[DataProvider('allowedCorpus')]
    public function test_it_allows_clean_messages_and_false_positive_guards(string $body): void
    {
        $result = $this->moderation->evaluate($body);

        $this->assertSame('allowed', $result['tier'], "Expected \"$body\" to be allowed.");
    }

    public static function allowedCorpus(): array
    {
        return [
            'plain greeting' => ['Hi, I booked your plumbing service for tomorrow.'],
            'peso amount' => ['Is ₱1500000 okay for the full job?'],
            'time of day' => ['See you at 9:00am tomorrow.'],
            'short house number' => ['1234'],
            'webis url allowlisted' => ['You can see it on webis.com/services/1'],
            'question about materials' => ['Do I need to prepare the materials myself?'],
        ];
    }
}
