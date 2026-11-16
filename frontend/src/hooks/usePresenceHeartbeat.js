import { useEffect } from 'react';
import { api } from '@/services/api/client';
import { useAuth } from '@/hooks/useAuth';

const HEARTBEAT_MS = 30_000;

/** Keeps last_seen_at current while a signed-in user has the app visible. */
export function usePresenceHeartbeat() {
  const { user } = useAuth();

  useEffect(() => {
    if (!user) return undefined;

    const send = () => {
      if (document.visibilityState === 'visible') {
        api.post('/auth/presence').catch(() => {});
      }
    };
    const onVisibilityChange = () => {
      if (document.visibilityState === 'visible') send();
    };

    send();
    const timer = window.setInterval(send, HEARTBEAT_MS);
    document.addEventListener('visibilitychange', onVisibilityChange);

    return () => {
      window.clearInterval(timer);
      document.removeEventListener('visibilitychange', onVisibilityChange);
    };
  }, [user]);
}
