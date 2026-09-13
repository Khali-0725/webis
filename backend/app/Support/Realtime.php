<?php

namespace App\Support;

use App\Events\UserDataChanged;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single entry point services use to push a change notification.
 *
 * A failing broadcast (Pusher down, key misconfigured, quota exhausted)
 * must never fail the request that already committed the real write - the
 * SPA still has polling underneath, so the only cost of a dropped push is a
 * slower update, not a lost one. Call this *after* the DB transaction has
 * committed, never inside it.
 */
final class Realtime
{
    /**
     * @param  iterable<int|null>  $userIds  null entries are skipped
     * @param  'messages'|'bookings'|'payments'  $scope
     * @param  array<string, int|string>  $meta
     */
    public static function push(iterable $userIds, string $scope, array $meta = []): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(fn ($id) => $id === null ? null : (int) $id, [...$userIds]),
        )));

        if ($ids === []) {
            return;
        }

        try {
            broadcast(new UserDataChanged($ids, $scope, $meta));
        } catch (Throwable $e) {
            Log::warning('Realtime push failed', [
                'scope' => $scope,
                'user_ids' => $ids,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
