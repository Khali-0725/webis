import { useEffect, useState } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Sidebar } from '@/components/layout/Sidebar';
import { Icon } from '@/components/ui/Icon';
import { Avatar } from '@/components/ui/Avatar';
import { useAuth } from '@/hooks/useAuth';

/**
 * The shell used by all three portals: fixed navy sidebar on desktop, a
 * slide-over drawer below the lg breakpoint, and a white topbar carrying the
 * page title, notification bell and the signed-in user's name.
 */
export function DashboardLayout({ title }) {
  const { user } = useAuth();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const location = useLocation();
  const [lastPath, setLastPath] = useState(location.pathname);

  // Close the drawer whenever the route changes - including on browser
  // back/forward, which no click handler would catch.
  //
  // This is React's "adjust state during render" pattern rather than an effect:
  // an effect would run after paint, briefly showing the drawer over the new
  // page, and calling setState directly inside one is a lint error for that
  // reason. Adjusting here re-renders before anything reaches the screen.
  if (lastPath !== location.pathname) {
    setLastPath(location.pathname);
    setDrawerOpen(false);
  }

  // Close on Escape.
  useEffect(() => {
    if (!drawerOpen) return undefined;

    const onKey = (event) => event.key === 'Escape' && setDrawerOpen(false);
    window.addEventListener('keydown', onKey);

    return () => window.removeEventListener('keydown', onKey);
  }, [drawerOpen]);

  return (
    <div className="min-h-screen bg-canvas">
      <a
        href="#main"
        className="sr-only-focusable fixed left-4 top-4 z-50 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white"
      >
        Skip to main content
      </a>

      {/* Desktop sidebar */}
      <aside className="fixed inset-y-0 left-0 z-30 hidden w-60 shadow-sidebar lg:block">
        <Sidebar />
      </aside>

      {/* Mobile drawer */}
      {drawerOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <button
            type="button"
            aria-label="Close navigation"
            className="absolute inset-0 bg-navy-950/50"
            onClick={() => setDrawerOpen(false)}
          />
          <div className="absolute inset-y-0 left-0 w-64 animate-fade-in shadow-xl">
            <Sidebar onNavigate={() => setDrawerOpen(false)} />
          </div>
        </div>
      )}

      <div className="lg:pl-60">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-line bg-white px-4 sm:px-6">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setDrawerOpen(true)}
              className="rounded-lg p-2 text-navy-700 hover:bg-canvas lg:hidden"
              aria-label="Open navigation"
              aria-expanded={drawerOpen}
            >
              <Icon name="menu" />
            </button>
            <h1 className="font-display text-lg font-semibold text-navy-800">{title}</h1>
          </div>

          <div className="flex items-center gap-3">
            <button
              type="button"
              className="relative rounded-lg p-2 text-navy-700 hover:bg-canvas"
              aria-label="Notifications"
            >
              <Icon name="bell" />
            </button>
            <div className="flex items-center gap-2">
              <Avatar
                initials={user?.initials}
                name={user?.full_name}
                src={user?.avatar_url}
                size="sm"
              />
              <span className="hidden text-sm font-medium text-ink sm:block">
                {user?.full_name}
              </span>
            </div>
          </div>
        </header>

        <main id="main" className="mx-auto max-w-7xl px-4 py-6 sm:px-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
