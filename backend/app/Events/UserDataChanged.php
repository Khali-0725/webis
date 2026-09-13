<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * One generic "something you care about changed" push, sent to each affected
 * user's private channel. The payload only names *what kind* of thing changed
 * (a scope plus a couple of ids), never the data itself - the SPA reacts by
 * invalidating the matching React Query keys and refetching through the
 * normal, policy-guarded API. That keeps authorization in exactly one place
 * and means nothing sensitive ever transits the broadcast provider.
 *
 * ShouldBroadcastNow rather than ShouldBroadcast: the Render free tier runs
 * `php artisan serve` only, with no queue worker, so a queued broadcast
 * would sit in the `jobs` table forever.
 */
class UserDataChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  int[]  $userIds
     * @param  'messages'|'bookings'|'payments'  $scope
     * @param  array<string, int|string>  $meta
     */
    public function __construct(
        public readonly array $userIds,
        public readonly string $scope,
        public readonly array $meta = [],
    ) {
    }

    /**
     * @return PrivateChannel[]
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (int $id) => new PrivateChannel("App.Models.User.{$id}"),
            array_values(array_unique($this->userIds)),
        );
    }

    public function broadcastAs(): string
    {
        return 'data.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['scope' => $this->scope, ...$this->meta];
    }
}
