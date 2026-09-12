import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { useAuth } from '@/hooks/useAuth';

export default function NotFoundPage() {
  const { isAuthenticated, user } = useAuth();
  const target = isAuthenticated ? user.home_path : '/';

  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center px-4 text-center">
      <p className="font-display text-5xl font-bold text-navy-200">404</p>
      <h1 className="mt-3 font-display text-xl font-semibold text-navy-800">Page not found</h1>
      <p className="mt-1 max-w-sm text-sm text-ink-muted">
        The page you were looking for does not exist or has moved.
      </p>
      <Link to={target} className="mt-5">
        <Button>{isAuthenticated ? 'Back to dashboard' : 'Back to home'}</Button>
      </Link>
    </div>
  );
}
