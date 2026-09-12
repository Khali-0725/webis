import { NavLink } from 'react-router-dom';
import { cn } from '@/utils/cn';
import { Icon } from '@/components/ui/Icon';
import { Logo } from '@/components/ui/Logo';
import { Avatar } from '@/components/ui/Avatar';
import { useAuth, useLogout } from '@/hooks/useAuth';
import { useUnreadCount } from '@/hooks/useUnreadCount';
import { ROLES } from '@/constants';

/**
 * Navigation per role. Route paths that do not exist yet are marked
 * `disabled` so the sidebar shows the full information architecture from the
 * mockups without offering dead links.
 */
const NAV = {
  [ROLES.CLIENT]: [
    { to: '/client/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { to: '/search', label: 'Search Services', icon: 'search' },
    { to: '/client/bookings', label: 'My Bookings', icon: 'bookings' },
    { to: '/client/messages', label: 'Messages', icon: 'message' },
    { to: '/client/reviews', label: 'Reviews', icon: 'star' },
    { to: '/profile', label: 'Profile', icon: 'user', disabled: false },
  ],
  [ROLES.PROVIDER]: [
    { to: '/provider/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { to: '/provider/profile', label: 'Business Profile', icon: 'user' },
    { to: '/provider/verification', label: 'Verification', icon: 'shield' },
    { to: '/provider/bookings', label: 'Bookings', icon: 'bookings' },
    { to: '/provider/availability', label: 'Availability', icon: 'calendar' },
    { to: '/provider/services', label: 'Services', icon: 'wrench' },
    { to: '/provider/payment-methods', label: 'Payment Methods', icon: 'wallet' },
    { to: '/provider/messages', label: 'Messages', icon: 'message' },
    { to: '/provider/earnings', label: 'Earnings', icon: 'wallet' },
    { to: '/provider/reviews', label: 'Reviews', icon: 'star' },
    { to: '/profile', label: 'Profile', icon: 'user', disabled: false },
  ],
  [ROLES.ADMIN]: [
    { to: '/admin/dashboard', label: 'Dashboard', icon: 'dashboard' },
    { to: '/admin/users', label: 'Users', icon: 'users' },
    { to: '/admin/verification', label: 'Verifications', icon: 'shield' },
    { to: '/admin/providers', label: 'Providers', icon: 'shield' },
    { to: '/admin/categories', label: 'Categories', icon: 'tag' },
    { to: '/admin/services', label: 'Services', icon: 'wrench' },
    { to: '/admin/barangays', label: 'Barangays', icon: 'pin' },
    { to: '/admin/bookings', label: 'Bookings', icon: 'bookings' },
    { to: '/admin/payments', label: 'Payments', icon: 'wallet' },
    { to: '/admin/violations', label: 'Chat Violations', icon: 'flag' },
    { to: '/admin/reports', label: 'Reports', icon: 'flag' },
    { to: '/admin/audit-logs', label: 'Audit Logs', icon: 'clipboard' },
    { to: '/admin/analytics', label: 'Analytics', icon: 'chart' },
    { to: '/admin/settings', label: 'Platform Settings', icon: 'settings' },
    { to: '/profile', label: 'Profile', icon: 'user', disabled: false },
  ],
};

const PORTAL_LABEL = {
  [ROLES.CLIENT]: 'Client Portal',
  [ROLES.PROVIDER]: 'Provider Portal',
  [ROLES.ADMIN]: 'Admin Portal',
};

export function Sidebar({ onNavigate }) {
  const { user } = useAuth();
  const { logout, isPending } = useLogout();
  const unreadCount = useUnreadCount();

  if (!user) return null;

  const items = NAV[user.role] ?? [];

  return (
    <div className="flex h-full flex-col bg-navy-800 text-white">
      <div className="px-5 py-5">
        <Logo variant="light" sublabel={PORTAL_LABEL[user.role]} showMark={false} />
      </div>

      <div className="mx-3 flex items-center gap-3 rounded-lg bg-white/5 px-3 py-3">
        <Avatar initials={user.initials} name={user.full_name} src={user.avatar_url} size="md" />
        <div className="min-w-0">
          <p className="truncate text-sm font-semibold">{user.full_name}</p>
          <p className="truncate text-[11px] text-white/60">{user.role_label}</p>
        </div>
      </div>

      <nav aria-label="Main navigation" className="mt-5 flex-1 overflow-y-auto px-3 pb-4">
        <ul className="space-y-1">
          {items.map((item) => (
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
                  onClick={onNavigate}
                  className={({ isActive }) =>
                    cn(
                      'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                      isActive
                        ? 'bg-navy-600 font-semibold text-white'
                        : 'text-white/75 hover:bg-white/5 hover:text-white',
                    )
                  }
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
      </nav>

      <div className="border-t border-white/10 p-3">
        <button
          type="button"
          onClick={logout}
          disabled={isPending}
          className="flex w-full items-center gap-3 rounded-lg bg-red-600/90 px-3 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-600 disabled:opacity-60"
        >
          <Icon name="logout" className="h-[18px] w-[18px]" />
          {isPending ? 'Signing out…' : 'Logout'}
        </button>
      </div>
    </div>
  );
}
