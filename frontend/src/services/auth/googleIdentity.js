const SCRIPT_SRC = 'https://accounts.google.com/gsi/client';

/**
 * Whether "Continue with Google" should appear at all. False with no env
 * change needed to ship without it - same pattern as Pusher's
 * `realtimeEnabled` in services/realtime/echo.js.
 */
export const googleSignInEnabled = Boolean(import.meta.env.VITE_GOOGLE_CLIENT_ID);

let scriptPromise = null;

/**
 * Loads the Google Identity Services script exactly once, however many
 * GoogleButtons mount. Concurrent/repeat callers share one in-flight promise
 * - same pattern as ensureCsrfCookie() in services/api/client.js.
 */
function loadScript() {
  if (scriptPromise) return scriptPromise;

  scriptPromise = new Promise((resolve, reject) => {
    if (window.google?.accounts?.id) {
      resolve();
      return;
    }

    const script = document.createElement('script');
    script.src = SCRIPT_SRC;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve();
    script.onerror = () => {
      scriptPromise = null;
      reject(new Error('Failed to load Google Identity Services.'));
    };
    document.head.appendChild(script);
  });

  return scriptPromise;
}

/**
 * Loads the script if needed, then (re)initializes the client with the given
 * callback. Re-initializing on every call is deliberate and cheap - it is
 * how a fresh `onCredential` closure (capturing whatever the calling
 * component's latest props/state are) becomes the one Google actually fires,
 * without the caller having to manage a stable ref itself.
 *
 * @param {string} clientId
 * @param {(response: { credential: string }) => void} callback
 */
export async function ensureGoogleIdentity(clientId, callback) {
  await loadScript();

  window.google.accounts.id.initialize({
    client_id: clientId,
    callback,
    auto_select: false,
    cancel_on_tap_outside: true,
  });
}
