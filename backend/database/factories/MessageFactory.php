<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->sentence(),
            'moderation_status' => ModerationStatus::Allowed,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function warned(): static
    {
        return $this->state(fn () => ['moderation_status' => ModerationStatus::Warned]);
    }

    public function flagged(): static
    {
        return $this->state(fn () => ['moderation_status' => ModerationStatus::Flagged]);
    }
}
