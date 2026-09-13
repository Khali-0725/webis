import { useQuery } from '@tanstack/react-query';
import { conversationApi } from '@/services/api/conversationApi';
import { queryKeys } from '@/services/api/queryClient';
import { POLL_SLOW } from '@/services/realtime/echo';
import { useAuth } from '@/hooks/useAuth';
import { ROLES } from '@/constants';

/**
 * Refreshed by a realtime push when Pusher is configured; the interval is
 * only the fallback (see src/services/realtime/echo.js).
 */
export function useUnreadCount() {
  const { isAuthenticated, role } = useAuth();
  const canMessage = role === ROLES.CLIENT || role === ROLES.PROVIDER;

  const { data } = useQuery({
    queryKey: queryKeys.conversations.unreadCount,
    queryFn: conversationApi.unreadCount,
    enabled: isAuthenticated && canMessage,
    refetchInterval: POLL_SLOW,
  });

  return data?.count ?? 0;
}
