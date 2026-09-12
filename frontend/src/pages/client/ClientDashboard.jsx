import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ApiStatusCard } from '@/components/layout/ApiStatusCard';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader, CardBody } from '@/components/ui/Card';
import { Badge, StatusBadge } from '@/components/ui/Badge';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { useAuth } from '@/hooks/useAuth';
import { bookingApi } from '@/services/api/bookingApi';
import { conversationApi } from '@/services/api/conversationApi';
import { paymentApi } from '@/services/api/paymentApi';
import { queryKeys } from '@/services/api/queryClient';
import { PAYMENT_STATUS_META } from '@/constants';

export default function ClientDashboard() {
  const { user } = useAuth();

  const bookingsQuery = useQuery({
    queryKey: queryKeys.bookings.list({ per_page: 5 }),
    queryFn: () => bookingApi.list({ per_page: 5 }),
  });

  const conversationsQuery = useQuery({
    queryKey: queryKeys.conversations.list,
    queryFn: conversationApi.list,
  });

  const paymentsQuery = useQuery({
    queryKey: queryKeys.payments.list({ per_page: 5 }),
    queryFn: () => paymentApi.list({ per_page: 5 }),
  });

  return (
    <div className="space-y-5">
      <Alert tone="info" title={`Welcome back, ${user?.first_name}.`}>
        Search for a service and send a booking request to get started.
      </Alert>

      <ApiStatusCard />

      <Card>
        <CardHeader
          title="My Bookings"
          action={
            <Link to="/client/bookings">
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
            <EmptyState
              title="No bookings yet"
              description="Search for a service and send a booking request to see it here."
              action={
                <Link to="/search">
                  <Button size="sm">Search services</Button>
                </Link>
              }
            />
          )}
          {!bookingsQuery.isPending && !bookingsQuery.isError && bookingsQuery.data.items.length > 0 && (
            <ul className="space-y-2">
              {bookingsQuery.data.items.map((booking) => (
                <li key={booking.id} className="flex items-center justify-between text-sm">
                  <Link to={`/client/bookings/${booking.id}`} className="text-ink hover:text-brand">
                    {booking.service?.title} — {booking.provider?.business_name} ({booking.scheduled_date})
                  </Link>
                  <StatusBadge status={booking.status} />
                </li>
              ))}
            </ul>
          )}
        </CardBody>
      </Card>

      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <CardHeader
            title="Messages"
            action={
              <Link to="/client/messages">
                <Button size="sm" variant="outline">
                  View all
                </Button>
              </Link>
            }
          />
          <CardBody>
            {conversationsQuery.isPending && <LoadingState label="Loading conversations…" />}
            {conversationsQuery.isError && (
              <ErrorState description={conversationsQuery.error?.message} onRetry={() => conversationsQuery.refetch()} />
            )}
            {!conversationsQuery.isPending && !conversationsQuery.isError && conversationsQuery.data.length === 0 && (
              <EmptyState title="No conversations yet" description="Message a provider from their service page." />
            )}
            {!conversationsQuery.isPending && !conversationsQuery.isError && conversationsQuery.data.length > 0 && (
              <ul className="space-y-2">
                {conversationsQuery.data.slice(0, 5).map((conversation) => (
                  <li key={conversation.id} className="flex items-center justify-between text-sm">
                    <Link to={`/client/messages/${conversation.id}`} className="truncate text-ink hover:text-brand">
                      {conversation.other_participant?.full_name ?? 'Unknown user'}
                    </Link>
                    {conversation.unread_count > 0 && (
                      <span className="rounded-full bg-brand px-2 py-0.5 text-xs font-semibold text-white">
                        {conversation.unread_count}
                      </span>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Payments" />
          <CardBody>
            {paymentsQuery.isPending && <LoadingState label="Loading payments…" />}
            {paymentsQuery.isError && (
              <ErrorState description={paymentsQuery.error?.message} onRetry={() => paymentsQuery.refetch()} />
            )}
            {!paymentsQuery.isPending && !paymentsQuery.isError && paymentsQuery.data.items.length === 0 && (
              <EmptyState title="No payments yet" description="Payment records appear here once you book a service." />
            )}
            {!paymentsQuery.isPending && !paymentsQuery.isError && paymentsQuery.data.items.length > 0 && (
              <ul className="space-y-2">
                {paymentsQuery.data.items.map((payment) => {
                  const meta = PAYMENT_STATUS_META[payment.status] ?? { label: payment.status_label, tone: 'neutral' };
                  return (
                    <li key={payment.id} className="flex items-center justify-between text-sm">
                      <Link to={`/client/bookings/${payment.booking_id}`} className="text-ink hover:text-brand">
                        {payment.amount != null ? `₱${payment.amount}` : 'Amount TBD'}
                      </Link>
                      <Badge tone={meta.tone}>{meta.label}</Badge>
                    </li>
                  );
                })}
              </ul>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
