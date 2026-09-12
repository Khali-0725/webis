import { useQuery } from '@tanstack/react-query';
import { conversationApi } from '@/services/api/conversationApi';
import { queryKeys } from '@/services/api/queryClient';
import { useAuth } from '@/hooks/useAuth';
import { ROLES } from '@/constants';

/**
 * Polled every 30s per the thesis's own scope decision (no websockets) -
 * see docs/PHASE-0-REQUIREMENTS-AUDIT.md's Q-3.
 */
export function useUnreadCount() {
  const { isAuthenticated, role } = useAuth();
  const canMessage = role === ROLES.CLIENT || role === ROLES.PROVIDER;

  const { data } = useQuery({
    queryKey: queryKeys.conversations.unreadCount,
    queryFn: conversationApi.unreadCount,
    enabled: isAuthenticated && canMessage,
    refetchInterval: 30_000,
  });

  return data?.count ?? 0;
}
