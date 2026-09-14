<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Only the sender may edit or unsend their own message. The
     * ConversationPolicy::view check on the parent runs first in the
     * controller, so a non-participant never reaches this.
     */
    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id;
    }

    public function delete(User $user, Message $message): bool
    {
        return $this->update($user, $message);
    }
}
