import { useState } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import { cn } from '@/utils/cn';
import { Logo } from '@/components/ui/Logo';
import { Button } from '@/components/ui/Button';
import { Icon } from '@/components/ui/Icon';
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
  const [menuOpen, setMenuOpen] = useState(false);
  const { pathname } = useLocation();
  const [lastPath, setLastPath] = useState(pathname);
  if (lastPath !== pathname) {
    setLastPath(pathname);
    setMenuOpen(false);
  }

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

          <div className="hidden items-center gap-2 md:flex">
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
          <button
            type="button"
            className="rounded-lg p-3 text-navy-700 hover:bg-canvas md:hidden"
            aria-label={menuOpen ? 'Close public navigation' : 'Open public navigation'}
            aria-expanded={menuOpen}
            aria-controls="public-mobile-navigation"
            onClick={() => setMenuOpen((open) => !open)}
            onKeyDown={(event) => event.key === 'Escape' && setMenuOpen(false)}
          >
            <Icon name={menuOpen ? 'close' : 'menu'} />
          </button>
        </div>
        {menuOpen && (
          <nav id="public-mobile-navigation" aria-label="Mobile primary" className="border-t border-line px-4 py-3 md:hidden" onKeyDown={(event) => event.key === 'Escape' && setMenuOpen(false)}>
            {LINKS.map((link) => (
              <NavLink key={link.to} to={link.to} end={link.end} className={({ isActive }) => cn('block rounded-lg px-3 py-3 text-sm', isActive ? 'bg-navy-50 font-semibold text-navy-800' : 'text-ink-muted hover:bg-canvas')}>
                {link.label}
              </NavLink>
            ))}
            <div className="mt-2 flex flex-wrap gap-2 border-t border-line pt-3">
              {isAuthenticated ? (
                <Link to={user.home_path} className="rounded-lg bg-navy-700 px-4 py-3 text-sm font-semibold text-white">Go to dashboard</Link>
              ) : (
                <>
                  <Link to="/login" className="rounded-lg border border-line px-4 py-3 text-sm font-semibold text-navy-800">Login</Link>
                  <Link to="/register" className="rounded-lg bg-navy-700 px-4 py-3 text-sm font-semibold text-white">Register</Link>
                </>
              )}
            </div>
          </nav>
        )}
      </header>

      <main className="flex-1">
        <Outlet />
      </main>

      <footer className="border-t border-line bg-canvas">
        <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-ink-muted sm:flex-row sm:px-6">
          <p>WEBIS — Web-Based Platform for Independent Service Providers</p>
          <div className="flex items-center gap-4">
            <Link to="/privacy" className="hover:text-navy-700">
              Privacy Policy
            </Link>
            <p>Tanza, Cavite</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
