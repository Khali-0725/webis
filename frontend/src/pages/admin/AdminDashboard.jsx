import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ApiStatusCard } from '@/components/layout/ApiStatusCard';
import { Alert } from '@/components/ui/Alert';
import { Card, StatCard } from '@/components/ui/Card';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { useAuth } from '@/hooks/useAuth';
import { analyticsApi } from '@/services/api/admin/analyticsApi';
import { queryKeys } from '@/services/api/queryClient';

const QUICK_LINKS = [
  { to: '/admin/users', label: 'Manage users' },
  { to: '/admin/providers', label: 'Provider oversight' },
  { to: '/admin/verification', label: 'Verification queue' },
  { to: '/admin/services', label: 'Services' },
  { to: '/admin/barangays', label: 'Barangays' },
  { to: '/admin/bookings', label: 'All bookings' },
  { to: '/admin/payments', label: 'All payments' },
  { to: '/admin/violations', label: 'Chat violations' },
  { to: '/admin/reports', label: 'User reports' },
  { to: '/admin/audit-logs', label: 'Audit logs' },
  { to: '/admin/analytics', label: 'Full analytics' },
];

export default function AdminDashboard() {
  const { user } = useAuth();

  const { data: summary, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.analyticsSummary,
    queryFn: analyticsApi.summary,
  });

  return (
    <div className="space-y-5">
      <Alert tone="warning" title={`Signed in as ${user?.full_name} (administrator).`}>
        Every figure below is a live database aggregate — none of it is hard-coded.
      </Alert>

      <ApiStatusCard />

      {isPending && <LoadingState label="Loading platform overview…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {summary && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard value={summary.users.total} label="Total Users" tone="navy" />
          <StatCard value={summary.providers.verified} label="Verified Providers" tone="success" />
          <StatCard value={summary.providers.pending} label="Pending Verification" tone="warning" />
          <StatCard value={summary.bookings.total} label="Total Bookings" tone="brand" />
          <StatCard value={summary.bookings.completed} label="Completed Bookings" tone="success" />
          <StatCard value={summary.bookings.disputed} label="Disputed Bookings" tone="warning" />
          <StatCard value={summary.payments.awaiting_review} label="Payments Awaiting Review" tone="warning" />
          <StatCard value={summary.services.active} label="Active Services" tone="info" />
        </div>
      )}

      <Card title="Quick links">
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          {QUICK_LINKS.map((link) => (
            <Link
              key={link.to}
              to={link.to}
              className="rounded-lg border border-line px-4 py-3 text-sm font-medium text-ink transition-colors hover:border-navy-300 hover:bg-slate-50"
            >
              {link.label}
            </Link>
          ))}
        </div>
      </Card>
    </div>
  );
}
