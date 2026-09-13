import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useAuth } from '@/hooks/useAuth';
import { disconnectEcho, getEcho } from '@/services/realtime/echo';

/**
 * Query-key prefixes to invalidate per push scope. The server only ever says
 * *what kind* of thing changed; the data itself is refetched through the
 * normal policy-guarded API, so nothing here trusts the payload.
 */
const KEYS_BY_SCOPE = {
  messages: [['conversations']],
  bookings: [['bookings']],
  payments: [['payments'], ['bookings'], ['provider', 'earnings']],
};

/**
 * Subscribes the signed-in user to their private channel for the lifetime of
 * the session. Mounted once at the app root; never call it from a page.
 */
export function useRealtimeSync() {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const userId = user?.id ?? null;

  useEffect(() => {
    if (userId === null) {
      disconnectEcho();
      return undefined;
    }

    const echo = getEcho();
    if (!echo) return undefined;

    const channelName = `App.Models.User.${userId}`;

    echo.private(channelName).listen('.data.changed', (payload) => {
      const keys = KEYS_BY_SCOPE[payload?.scope] ?? [];

      keys.forEach((queryKey) => queryClient.invalidateQueries({ queryKey }));
    });

    return () => echo.leave(channelName);
  }, [userId, queryClient]);
}
