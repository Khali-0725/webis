import { Link, NavLink, Outlet } from 'react-router-dom';
import { cn } from '@/utils/cn';
import { Logo } from '@/components/ui/Logo';
import { Button } from '@/components/ui/Button';
import { useAuth } from '@/hooks/useAuth';

const LINKS = [
  { to: '/', label: 'Home', end: true },
  { to: '/search', label: 'Services' },
  { to: '/providers', label: 'Providers' },
  { to: '/about', label: 'About' },
  { to: '/contact', label: 'Contact' },
];

export function PublicLayout() {
  const { user, isAuthenticated } = useAuth();

  return (
    <div className="flex min-h-screen flex-col bg-white">
      <header className="border-b border-line bg-white">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
          <Link to="/" aria-label="WEBIS home">
            <Logo />
          </Link>

          <nav aria-label="Primary" className="hidden items-center gap-7 md:flex">
            {LINKS.map((link) =>
              link.disabled ? (
                <span
                  key={link.to}
                  aria-disabled="true"
                  title="Available in a later development phase"
                  className="cursor-not-allowed text-sm text-ink-soft"
                >
                  {link.label}
                </span>
              ) : (
                <NavLink
                  key={link.to}
                  to={link.to}
                  end={link.end}
                  className={({ isActive }) =>
                    cn(
                      'text-sm transition-colors',
                      isActive ? 'font-semibold text-navy-800' : 'text-ink-muted hover:text-navy-700',
                    )
                  }
                >
                  {link.label}
                </NavLink>
              ),
            )}
          </nav>

          <div className="flex items-center gap-2">
            {isAuthenticated ? (
              <Link to={user.home_path}>
                <Button size="sm">Go to dashboard</Button>
              </Link>
            ) : (
              <>
                <Link to="/login">
                  <Button variant="outline" size="sm">
                    Login
                  </Button>
                </Link>
                <Link to="/register">
                  <Button size="sm">Register</Button>
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      <main className="flex-1">
        <Outlet />
      </main>

      <footer className="border-t border-line bg-canvas">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-ink-muted sm:flex-row sm:px-6">
          <p>WEBIS — Web-Based Platform for Independent Service Providers</p>
          <p>Tanza, Cavite</p>
        </div>
      </footer>
    </div>
  );
}
