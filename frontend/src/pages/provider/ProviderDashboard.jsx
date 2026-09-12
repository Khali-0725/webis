import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ApiStatusCard } from '@/components/layout/ApiStatusCard';
import { Alert } from '@/components/ui/Alert';
import { Badge, StatusBadge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader, CardBody, StatCard } from '@/components/ui/Card';
import { LineChart } from '@/components/ui/LineChart';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { useAuth } from '@/hooks/useAuth';
import { providerApi } from '@/services/api/providerApi';
import { bookingApi } from '@/services/api/bookingApi';
import { earningsApi } from '@/services/api/earningsApi';
import { queryKeys } from '@/services/api/queryClient';
import { VERIFICATION_STATUS_META } from '@/constants';

function formatPeso(value) {
  return `₱${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export default function ProviderDashboard() {
  const { user } = useAuth();

  const profileQuery = useQuery({
    queryKey: queryKeys.provider.profile,
    queryFn: providerApi.getMyProfile,
  });

  const servicesQuery = useQuery({
    queryKey: queryKeys.provider.services,
    queryFn: () => providerApi.listMyServices({ per_page: 5 }),
  });

  const bookingsQuery = useQuery({
    queryKey: queryKeys.bookings.list({ status: 'pending' }),
    queryFn: () => bookingApi.list({ status: 'pending', per_page: 5 }),
  });

  const earningsSummaryQuery = useQuery({
    queryKey: queryKeys.provider.earningsSummary,
    queryFn: earningsApi.summary,
  });

  const earningsOverTimeQuery = useQuery({
    queryKey: queryKeys.provider.earningsOverTime,
    queryFn: earningsApi.overTime,
  });

  const verification = profileQuery.data
    ? VERIFICATION_STATUS_META[profileQuery.data.verification_status]
    : null;

  return (
    <div className="space-y-5">
      <Alert tone="info" title={`Welcome back, ${user?.first_name}.`}>
        Manage your services, availability and booking requests below.
      </Alert>

      <ApiStatusCard />

      <Card>
        <CardHeader
          title="Pending Booking Requests"
          action={
            <Link to="/provider/bookings">
              <Button size="sm" variant="outline">
                View all
              </Button>
            </Link>
          }
        />
        <CardBody>
          {bookingsQuery.isPending && <LoadingState label="Loading bookings…" />}
          {bookingsQuery.isError && (
            <ErrorState description={bookingsQuery.error?.message} onRetry={() => bookingsQuery.refetch()} />
          )}
          {!bookingsQuery.isPending && !bookingsQuery.isError && bookingsQuery.data.items.length === 0 && (
            <EmptyState title="No pending requests" description="New booking requests from clients will show up here." />
          )}
          {!bookingsQuery.isPending && !bookingsQuery.isError && bookingsQuery.data.items.length > 0 && (
            <ul className="space-y-2">
              {bookingsQuery.data.items.map((booking) => (
                <li key={booking.id} className="flex items-center justify-between text-sm">
                  <Link to={`/provider/bookings/${booking.id}`} className="text-ink hover:text-brand">
                    {booking.service?.title} — {booking.client?.full_name} ({booking.scheduled_date})
                  </Link>
                  <StatusBadge status={booking.status} />
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard
          value={earningsSummaryQuery.data ? formatPeso(earningsSummaryQuery.data.total_earnings) : '—'}
          label="Total Earnings"
          tone="success"
        />
        <StatCard
          value={earningsSummaryQuery.data?.verified_count ?? '—'}
          label="Verified Payments"
          tone="brand"
        />
        <StatCard
          value={earningsSummaryQuery.data?.awaiting_review_count ?? '—'}
          label="Awaiting Your Review"
          tone="warning"
        />
      </div>

      <Card>
        <CardHeader
          title="Earnings Over Time (last 30 days)"
          action={
            <Link to="/provider/earnings">
              <Button size="sm" variant="outline">
                View details
              </Button>
            </Link>
          }
        />
        <CardBody>
          {earningsOverTimeQuery.isPending && <LoadingState label="Loading…" />}
          {earningsOverTimeQuery.isError && (
            <ErrorState
              description={earningsOverTimeQuery.error?.message}
              onRetry={() => earningsOverTimeQuery.refetch()}
            />
          )}
          {!earningsOverTimeQuery.isPending && !earningsOverTimeQuery.isError && (
            <LineChart
              points={earningsOverTimeQuery.data}
              valueKey="total"
              color="#0f766e"
              formatValue={formatPeso}
            />
          )}
        </CardBody>
      </Card>

      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardHeader
            title="My Services"
            action={
              <Link to="/provider/services">
                <Button size="sm" variant="outline">
                  Manage
                </Button>
              </Link>
            }
          />
          <CardBody>
            {servicesQuery.isPending && <LoadingState label="Loading services…" />}
            {servicesQuery.isError && (
              <ErrorState description={servicesQuery.error?.message} onRetry={() => servicesQuery.refetch()} />
            )}
            {!servicesQuery.isPending && !servicesQuery.isError && servicesQuery.data.items.length === 0 && (
              <EmptyState
                title="No services yet"
                description="Create your first service to start getting booked."
                action={
                  <Link to="/provider/services/new">
                    <Button size="sm">Create a service</Button>
                  </Link>
                }
              />
            )}
            {!servicesQuery.isPending && !servicesQuery.isError && servicesQuery.data.items.length > 0 && (
              <ul className="space-y-2">
                {servicesQuery.data.items.map((service) => (
                  <li key={service.id} className="flex items-center justify-between text-sm">
                    <span className="text-ink">{service.title}</span>
                    <Badge tone={service.is_published ? 'success' : 'neutral'}>
                      {service.is_published ? 'Published' : 'Draft'}
                    </Badge>
                  </li>
                ))}
              </ul>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader
            title="Verification"
            action={
              <Link to="/provider/verification">
                <Button size="sm" variant="outline">
                  {verification?.label === 'Verified' ? 'View' : 'Upload documents'}
                </Button>
              </Link>
            }
          />
          <CardBody>
            {profileQuery.isPending && <LoadingState label="Loading status…" />}
            {profileQuery.isError && (
              <ErrorState description={profileQuery.error?.message} onRetry={() => profileQuery.refetch()} />
            )}
            {verification && (
              <div className="flex items-center gap-3">
                <Badge tone={verification.tone}>{verification.label}</Badge>
                {profileQuery.data.rejection_reason && (
                  <p className="text-xs text-ink-muted">{profileQuery.data.rejection_reason}</p>
                )}
              </div>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
