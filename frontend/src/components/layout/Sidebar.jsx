import { NavLink, Link, matchPath, useLocation } from 'react-router-dom';
import { useState } from 'react';
import { cn } from '@/utils/cn';
import { Icon } from '@/components/ui/Icon';
import { Logo } from '@/components/ui/Logo';
import { Avatar } from '@/components/ui/Avatar';
import { useAuth, useLogout } from '@/hooks/useAuth';
import { useUnreadCount } from '@/hooks/useUnreadCount';
import { ROLE_LABELS, ROLES } from '@/constants';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';

/**
 * Navigation per role. Route paths that do not exist yet are marked
 * `disabled` so the sidebar shows the full information architecture from the
 * mockups without offering dead links.
 */
const NAV = {
  [ROLES.CLIENT]: [
    { to: '/client/dashboard', label: 'Dashboard', icon: 'dashboard', group: 'Overview' },
    { to: '/client/search', label: 'Search Services', icon: 'search', group: 'Services' },
    { to: '/client/providers', label: 'Search Providers', icon: 'users', group: 'Services' },
    { to: '/client/bookings', label: 'My Bookings', icon: 'bookings', group: 'Activity' },
    { to: '/client/messages', label: 'Messages', icon: 'message', group: 'Activity' },
    { to: '/client/reviews', label: 'Reviews', icon: 'star', group: 'Activity' },
    { to: '/client/reports', label: 'My Reports', icon: 'flag', group: 'Activity' },
  ],
  [ROLES.PROVIDER]: [
    { to: '/provider/dashboard', label: 'Dashboard', icon: 'dashboard', group: 'Overview' },
    { to: '/provider/profile', label: 'Business Profile', icon: 'user', group: 'Business' },
    { to: '/provider/verification', label: 'Verification', icon: 'shield', group: 'Business' },
    { to: '/provider/availability', label: 'Availability', icon: 'calendar', group: 'Business' },
    { to: '/provider/services', label: 'Services', icon: 'wrench', group: 'Business' },
    { to: '/provider/payment-methods', label: 'Payment Methods', icon: 'wallet', group: 'Business' },
    { to: '/provider/bookings', label: 'Bookings', icon: 'bookings', group: 'Work' },
    { to: '/provider/messages', label: 'Messages', icon: 'message', group: 'Work' },
    { to: '/provider/earnings', label: 'Earnings', icon: 'wallet', group: 'Work' },
    { to: '/provider/reviews', label: 'Reviews', icon: 'star', group: 'Feedback' },
    { to: '/provider/reports', label: 'My Reports', icon: 'flag', group: 'Feedback' },
  ],
  [ROLES.ADMIN]: [
    { to: '/admin/dashboard', label: 'Dashboard', icon: 'dashboard', group: 'Overview' },
    { to: '/admin/analytics', label: 'Analytics', icon: 'chart', group: 'Overview' },
    { to: '/admin/users', label: 'Users', icon: 'users', group: 'People' },
    { to: '/admin/providers', label: 'Providers', icon: 'shield', group: 'People' },
    { to: '/admin/verification', label: 'Verifications', icon: 'shield', group: 'People' },
    { to: '/admin/appeals', label: 'Appeals', icon: 'flag', group: 'People' },
    { to: '/admin/categories', label: 'Categories', icon: 'tag', group: 'Services' },
    { to: '/admin/services', label: 'Services', icon: 'wrench', group: 'Services' },
    { to: '/admin/barangays', label: 'Barangays', icon: 'pin', group: 'Services' },
    { to: '/admin/bookings', label: 'Bookings', icon: 'bookings', group: 'Operations' },
    { to: '/admin/payments', label: 'Payments', icon: 'wallet', group: 'Operations' },
    { to: '/admin/reviews', label: 'Reviews', icon: 'star', group: 'Moderation' },
    { to: '/admin/violations', label: 'Chat Violations', icon: 'warning', group: 'Moderation' },
    { to: '/admin/reports', label: 'Reports', icon: 'flag', group: 'Moderation' },
    { to: '/admin/audit-logs', label: 'Audit Logs', icon: 'clipboard', group: 'System' },
    { to: '/admin/settings', label: 'Platform Settings', icon: 'settings', group: 'System' },
  ],
};

NAV[ROLES.SUPER_ADMIN] = [
  ...NAV[ROLES.ADMIN].filter((item) => item.to !== '/admin/reviews'),
];

const PORTAL_LABEL = {
  [ROLES.CLIENT]: 'Client Portal',
  [ROLES.PROVIDER]: 'Provider Portal',
  [ROLES.ADMIN]: 'Admin Portal',
  [ROLES.SUPER_ADMIN]: 'Super Admin Portal',
};

export function Sidebar({ onNavigate }) {
  const { user } = useAuth();
  const { logout, isPending } = useLogout();
  const unreadCount = useUnreadCount();
  const [confirmLogout, setConfirmLogout] = useState(false);
  const { pathname } = useLocation();

  if (!user) return null;

  const items = NAV[user.role] ?? [];
  const groups = Object.entries(items.reduce((result, item) => {
    (result[item.group] ??= []).push(item);
    return result;
  }, {}));
  const isAdmin = [ROLES.ADMIN, ROLES.SUPER_ADMIN].includes(user.role);

  const renderLinks = (links) => (
    <ul className="space-y-1">
      {links.map((item) => (
        <li key={item.to}>
          {item.disabled ? (
            <span
              aria-disabled="true"
              title="Available in a later development phase"
              className="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/35"
            >
              <Icon name={item.icon} className="h-[18px] w-[18px]" />
              {item.label}
            </span>
          ) : (
            <NavLink
              to={item.to}
              end={item.end}
              onClick={onNavigate}
              className={({ isActive }) => cn(
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                isActive ? 'bg-navy-600 font-semibold text-white' : 'text-white/75 hover:bg-white/5 hover:text-white',
              )}
            >
              <Icon name={item.icon} className="h-[18px] w-[18px]" />
              <span className="flex-1">{item.label}</span>
              {item.to.endsWith('/messages') && unreadCount > 0 && (
                <span className="rounded-full bg-brand px-1.5 py-0.5 text-[11px] font-semibold text-white">
                  {unreadCount > 99 ? '99+' : unreadCount}
                </span>
              )}
            </NavLink>
          )}
        </li>
      ))}
    </ul>
  );

  return (
    <div className="flex h-full flex-col bg-navy-800 text-white">
      <div className="px-5 py-5">
        <Link to={user.home_path} onClick={onNavigate} aria-label="WEBIS dashboard">
          <Logo variant="light" sublabel={PORTAL_LABEL[user.role]} showMark={false} />
        </Link>
      </div>

      <nav aria-label="Main navigation" className="sidebar-scroll mt-2 min-h-0 flex-1 overflow-y-auto px-3 pb-3">
        <ul className="space-y-1">
          {groups.map(([group, links]) => {
            const active = links.some((item) => matchPath({ path: item.to, end: item.end ?? false }, pathname));
            return (
              <li key={group}>
                {isAdmin && group !== 'Overview' ? (
                  <details key={`${group}:${active}`} className="sidebar-group pt-2" open={active}>
                    <summary className="flex min-h-10 cursor-pointer list-none items-center justify-between rounded-lg px-3 text-[11px] font-semibold uppercase tracking-wider text-white/60 transition-colors hover:bg-white/5 hover:text-white">
                      <span>{group}</span>
                      <svg className="sidebar-group-chevron h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true" focusable="false">
                        <path d="m9 5 7 7-7 7" strokeLinecap="round" strokeLinejoin="round" />
                      </svg>
                    </summary>
                    {renderLinks(links)}
                  </details>
                ) : (
                  <>
                    <div className="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/50">{group}</div>
                    {renderLinks(links)}
                  </>
                )}
              </li>
            );
          })}
        </ul>
      </nav>

      <div className="border-t border-white/10 px-3 py-3">
        <NavLink
          to="/profile"
          onClick={onNavigate}
          aria-label="My profile"
          className={({ isActive }) => cn(
            'flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors hover:bg-white/10',
            isActive && 'bg-navy-600',
          )}
        >
          <Avatar src={user.avatar_url} initials={user.initials} name={user.full_name} size="sm" />
          <span className="min-w-0 flex-1">
            <span className="block truncate text-sm font-semibold text-white">{user.full_name}</span>
            <span className="block truncate text-xs text-white/60">{user.role_label ?? ROLE_LABELS[user.role]}</span>
          </span>
          <Icon name="chevronRight" className="h-4 w-4 text-white/50" />
        </NavLink>
      </div>

      <div className="border-t border-white/10 p-3">
        <button
          type="button"
          onClick={() => setConfirmLogout(true)}
          disabled={isPending}
          className="flex w-full items-center gap-3 rounded-lg border border-white/20 px-3 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10 disabled:opacity-60"
        >
          <Icon name="logout" className="h-[18px] w-[18px]" />
          {isPending ? 'Signing out…' : 'Logout'}
        </button>
      </div>
      <ConfirmDialog
        open={confirmLogout}
        onClose={() => setConfirmLogout(false)}
        onConfirm={logout}
        title="Are you sure you want to log out?"
        confirmLabel="Yes, Log Out"
        loading={isPending}
      />
    </div>
  );
}
