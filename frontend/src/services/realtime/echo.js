import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { api } from '@/services/api/client';

const key = import.meta.env.VITE_PUSHER_APP_KEY;
const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1';

/**
 * Realtime is strictly additive: with no key configured (a fresh local
 * checkout, a preview deploy) the app behaves exactly as before, on polling
 * alone. Pages read these intervals so they can back off when pushes are
 * doing the work.
 */
export const realtimeEnabled = Boolean(key);
export const POLL_FAST = realtimeEnabled ? 30_000 : 5_000;
export const POLL_SLOW = realtimeEnabled ? 120_000 : 30_000;

let echo = null;

export function getEcho() {
  if (!realtimeEnabled) return null;

  if (!echo) {
    echo = new Echo({
      broadcaster: 'pusher',
      Pusher,
      key,
      cluster,
      forceTLS: true,
      // Private-channel auth must ride the same Sanctum cookie session (and
      // CSRF header) as every other API call, so it goes through the shared
      // axios instance rather than pusher-js's own XHR.
      authorizer: (channel) => ({
        authorize: (socketId, callback) => {
          api
            .post('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name })
            .then((response) => callback(null, response.data))
            .catch((error) => callback(error, null));
        },
      }),
    });
  }

  return echo;
}

export function disconnectEcho() {
  echo?.disconnect();
  echo = null;
}
