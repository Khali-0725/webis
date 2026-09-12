<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'client_id' => User::factory()->client(),
            'provider_user_id' => User::factory()->provider(),
            'booking_id' => null,
            'client_unread_count' => 0,
            'provider_unread_count' => 0,
            'last_message_at' => null,
        ];
    }
}
