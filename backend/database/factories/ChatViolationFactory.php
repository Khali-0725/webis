<?php

namespace Database\Factories;

use App\Enums\ViolationAction;
use App\Enums\ViolationCategory;
use App\Enums\ViolationSeverity;
use App\Models\ChatViolation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatViolation>
 */
class ChatViolationFactory extends Factory
{
    protected $model = ChatViolation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'message_id' => null,
            'attempted_body' => 'Call me on 09171234567',
            'category' => ViolationCategory::PhoneNumber,
            'matched_rule' => 'ph_mobile_number',
            'severity' => ViolationSeverity::High,
            'action_taken' => ViolationAction::Blocked,
        ];
    }

    public function warned(): static
    {
        return $this->state(fn () => ['action_taken' => ViolationAction::Warned, 'severity' => ViolationSeverity::Low]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['action_taken' => ViolationAction::Blocked]);
    }
}
