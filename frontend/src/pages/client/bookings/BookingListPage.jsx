import { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { StatusBadge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { bookingApi } from '@/services/api/bookingApi';
import { queryKeys } from '@/services/api/queryClient';
import { BOOKING_STATUS_META } from '@/constants';

export default function BookingListPage() {
  const location = useLocation();
  const bookingCreated = location.state?.bookingCreated;
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.bookings.list({ status, page }),
    queryFn: () => bookingApi.list({ ...(status ? { status } : {}), page }),
    refetchInterval: 30_000,
  });

  return (
    <Card title="My Bookings">
      {bookingCreated && (
        <Alert tone="success" className="mb-4">
          Booking request {bookingCreated} sent. The provider will accept or decline it soon.
        </Alert>
      )}

      <div className="mb-4 flex items-center gap-2">
        <label htmlFor="status-filter" className="text-sm text-ink-muted">
          Status
        </label>
        <select
          id="status-filter"
          value={status}
          onChange={(event) => {
            setStatus(event.target.value);
            setPage(1);
          }}
          className="h-9 rounded-lg border border-line bg-white px-2 text-sm text-ink"
        >
          <option value="">All</option>
          {Object.entries(BOOKING_STATUS_META).map(([value, meta]) => (
            <option key={value} value={value}>
              {meta.label}
            </option>
          ))}
        </select>
      </div>

      {isPending && <LoadingState label="Loading bookings…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && data.items.length === 0 && (
        <EmptyState
          title="No bookings yet"
          description="Search for a service and send a booking request to see it here."
        />
      )}

      {!isPending && !isError && data.items.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Service</th>
                <th className="py-2 pr-4 font-medium">Provider</th>
                <th className="py-2 pr-4 font-medium">Date &amp; time</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium" />
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {data.items.map((booking) => (
                <tr key={booking.id}>
                  <td className="py-3 pr-4 text-ink">{booking.service?.title}</td>
                  <td className="py-3 pr-4 text-ink-muted">{booking.provider?.business_name}</td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {booking.scheduled_date} · {booking.scheduled_start_time?.slice(0, 5)}
                  </td>
                  <td className="py-3 pr-4">
                    <StatusBadge status={booking.status} />
                  </td>
                  <td className="py-3">
                    <Link to={`/client/bookings/${booking.id}`} className="text-sm font-medium text-brand hover:underline">
                      View
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />
    </Card>
  );
}
