import { useEffect } from 'react';
import { Logo } from '@/components/ui/Logo';

const DISPLAY_MS = 1600;

export function AuthWelcomeScreen({ user, onDone }) {
  useEffect(() => {
    const timer = window.setTimeout(onDone, DISPLAY_MS);
    return () => window.clearTimeout(timer);
  }, [onDone]);

  return (
    <div className="fixed inset-0 z-[70] grid place-items-center bg-gradient-to-br from-navy-700 via-navy-800 to-navy-950 px-6 text-white">
      <div className="animate-fade-in text-center">
        <Logo variant="light" className="justify-center" />
        <p className="mt-5 text-sm text-white/70">
          Welcome, {user?.first_name || 'there'}!
        </p>
      </div>
    </div>
  );
}
