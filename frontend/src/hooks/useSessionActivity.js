import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';
import { api, resetCsrfCookie } from '@/services/api/client';
import { useAuthStore } from '@/store/authStore';

const IDLE_MS = 30 * 60 * 1000;
const ACTIVITY_THROTTLE_MS = 60 * 1000;

/** User gestures renew the server clock; polling and background reads never do. */
export function useSessionActivity() {
  const user = useAuthStore((state) => state.user);
  const clearUser = useAuthStore((state) => state.clearUser);
  const setSessionEndedMessage = useAuthStore((state) => state.setSessionEndedMessage);
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  useEffect(() => {
    if (!user) return undefined;

    let lastGesture = Date.now();
    let lastSent = Date.now();
    let stopped = false;

    const expire = (message) => {
      if (stopped) return;
      stopped = true;
      clearUser();
      setSessionEndedMessage(message);
      queryClient.clear();
      resetCsrfCookie();
      navigate('/login', { replace: true, state: { sessionExpired: message } });
    };

    const activity = () => {
      const now = Date.now();
      // Do not revive a session that has already reached the inactivity limit.
      if (now - lastGesture >= IDLE_MS) {
        expire('Your session expired after 30 minutes of inactivity.');
        return;
      }
      lastGesture = now;
      if (now - lastSent < ACTIVITY_THROTTLE_MS) return;
      lastSent = now;
      api.post('/auth/activity').catch((error) => {
        if (error?.status === 401 || error?.status === 403) expire(error.message);
      });
    };

    const visible = () => {
      if (document.visibilityState === 'visible') activity();
    };

    const sessionEnded = (event) => {
      expire(event.detail?.message || 'Your session has ended. Please sign in again.');
    };

    window.addEventListener('pointerdown', activity);
    window.addEventListener('keydown', activity);
    window.addEventListener('scroll', activity, { passive: true });
    window.addEventListener('webis:session-ended', sessionEnded);
    document.addEventListener('visibilitychange', visible);
    const timer = window.setInterval(() => {
      if (Date.now() - lastGesture >= IDLE_MS) expire('Your session expired after 30 minutes of inactivity.');
    }, 15_000);

    return () => {
      stopped = true;
      window.clearInterval(timer);
      window.removeEventListener('pointerdown', activity);
      window.removeEventListener('keydown', activity);
      window.removeEventListener('scroll', activity);
      window.removeEventListener('webis:session-ended', sessionEnded);
      document.removeEventListener('visibilitychange', visible);
    };
  }, [user, clearUser, setSessionEndedMessage, queryClient, navigate]);
}
