import { useEffect, useRef, useState } from 'react';
import { ensureGoogleIdentity, googleSignInEnabled } from '@/services/auth/googleIdentity';
import { cn } from '@/utils/cn';

/**
 * Renders Google's own "Continue with Google" button via Google Identity
 * Services, styled (outline/pill) to sit naturally in WEBIS's auth forms.
 *
 * Deliberately not a hand-built button: a custom one calling
 * `google.accounts.id.prompt()` is subject to Google's One Tap cooldown
 * suppression and can silently stop showing anything. The rendered button
 * has none of that risk and is still brand-compliant.
 *
 * Degrades to rendering nothing when `VITE_GOOGLE_CLIENT_ID` is unset - same
 * "leave the key empty, the feature just doesn't appear" pattern this app
 * already uses for Pusher (see `realtimeEnabled` in services/realtime/echo.js).
 */
export function GoogleButton({ onCredential, disabled = false, text = 'continue_with' }) {
  const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID;
  const containerRef = useRef(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!googleSignInEnabled) return undefined;

    let cancelled = false;

    ensureGoogleIdentity(clientId, (response) => onCredential(response.credential)).then(() => {
      if (!cancelled) setReady(true);
    });

    return () => {
      cancelled = true;
    };
  }, [clientId, onCredential]);

  useEffect(() => {
    if (!ready || !containerRef.current) return;

    containerRef.current.innerHTML = '';

    window.google.accounts.id.renderButton(containerRef.current, {
      type: 'standard',
      theme: 'outline',
      size: 'large',
      shape: 'pill',
      text,
      logo_alignment: 'left',
      width: containerRef.current.offsetWidth || 320,
    });
  }, [ready, text]);

  if (!googleSignInEnabled) return null;

  return (
    <div
      ref={containerRef}
      className={cn('flex h-11 w-full justify-center', disabled && 'pointer-events-none opacity-60')}
      aria-disabled={disabled || undefined}
    />
  );
}
