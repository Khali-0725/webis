import { Outlet, Link } from 'react-router-dom';

/**
 * Centred card on the navy gradient, matching the Login mockup.
 */
export function AuthLayout() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-navy-700 via-navy-800 to-navy-950 px-4 py-10">
      <div className="w-full max-w-md">
        <div className="rounded-card bg-white p-7 shadow-xl sm:p-8">
          <Outlet />
        </div>

        <p className="mt-5 text-center text-xs text-white/50">
          <Link to="/" className="hover:text-white/80">
            ← Back to Home
          </Link>
        </p>
      </div>
    </div>
  );
}
