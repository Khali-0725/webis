import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { StatusBadge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { bookingApi } from '@/services/api/bookingApi';
import { queryKeys } from '@/services/api/queryClient';
import { BOOKING_STATUS_META } from '@/constants';

export default function BookingListPage() {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.bookings.list({ status, page }),
    queryFn: () => bookingApi.list({ ...(status ? { status } : {}), page }),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['bookings'] });

  const acceptMutation = useMutation({ mutationFn: bookingApi.accept, onSuccess: invalidate });
  const rejectMutation = useMutation({ mutationFn: (id) => bookingApi.reject(id), onSuccess: invalidate });

  return (
    <Card title="Bookings">
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
        <EmptyState title="No bookings yet" description="Booking requests from clients will appear here." />
      )}

      {!isPending && !isError && data.items.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Client</th>
                <th className="py-2 pr-4 font-medium">Service</th>
                <th className="py-2 pr-4 font-medium">Date &amp; time</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {data.items.map((booking) => (
                <tr key={booking.id}>
                  <td className="py-3 pr-4 text-ink">{booking.client?.full_name}</td>
                  <td className="py-3 pr-4 text-ink-muted">{booking.service?.title}</td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {booking.scheduled_date} · {booking.scheduled_start_time?.slice(0, 5)}
                  </td>
                  <td className="py-3 pr-4">
                    <StatusBadge status={booking.status} />
                  </td>
                  <td className="py-3">
                    <div className="flex gap-2">
                      {booking.status === 'pending' && (
                        <>
                          <Button
                            size="sm"
                            onClick={() => acceptMutation.mutate(booking.id)}
                            loading={acceptMutation.isPending && acceptMutation.variables === booking.id}
                          >
                            Accept
                          </Button>
                          <Button
                            size="sm"
                            variant="danger"
                            onClick={() => rejectMutation.mutate(booking.id)}
                            loading={rejectMutation.isPending && rejectMutation.variables === booking.id}
                          >
                            Reject
                          </Button>
                        </>
                      )}
                      <Link to={`/provider/bookings/${booking.id}`}>
                        <Button size="sm" variant="outline">
                          View
                        </Button>
                      </Link>
                    </div>
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
