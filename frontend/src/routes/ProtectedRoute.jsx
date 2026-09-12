import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Spinner } from '@/components/ui/Spinner';

/**
 * Route-level guard.
 *
 * IMPORTANT: this is a usability control, not a security control. It stops a
 * signed-out visitor seeing an empty shell and bouncing off a failed request.
 * Every endpoint behind it is independently enforced server-side by
 * `auth:sanctum`, the `role` middleware and a Policy - removing this component
 * would leak no data.
 */
export function ProtectedRoute({ allow }) {
  const { isAuthenticated, isReady, role, user } = useAuth();
  const location = useLocation();

  if (!isReady) {
    return (
      <div className="grid min-h-screen place-items-center bg-canvas">
        <Spinner className="h-7 w-7 text-navy-600" label="Restoring your session" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  if (Array.isArray(allow) && allow.length > 0 && !allow.includes(role)) {
    // Signed in, wrong portal: send them to their own instead of a dead end.
    return <Navigate to={user.home_path} replace />;
  }

  return <Outlet />;
}

/** Keeps a signed-in user away from /login and /register. */
export function GuestRoute() {
  const { isAuthenticated, isReady, user } = useAuth();

  if (!isReady) {
    return (
      <div className="grid min-h-screen place-items-center bg-navy-800">
        <Spinner className="h-7 w-7 text-white" label="Loading" />
      </div>
    );
  }

  return isAuthenticated ? <Navigate to={user.home_path} replace /> : <Outlet />;
}
