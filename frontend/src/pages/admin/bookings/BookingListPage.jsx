import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { StatusBadge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { bookingApi } from '@/services/api/bookingApi';
import { BOOKING_STATUS } from '@/constants';

const STATUS_OPTIONS = ['', ...Object.values(BOOKING_STATUS)];

export default function AdminBookingListPage() {
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const params = { ...(status ? { status } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'bookings', params],
    queryFn: () => bookingApi.list(params),
  });

  const bookings = data?.items ?? [];

  return (
    <Card title="All Bookings">
      <div className="mb-4">
        <select
          aria-label="Filter by status"
          className="h-10 rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
          value={status}
          onChange={(e) => {
            setStatus(e.target.value);
            setPage(1);
          }}
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s || 'All statuses'}
            </option>
          ))}
        </select>
      </div>

      {isPending && <LoadingState label="Loading bookings…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && bookings.length === 0 && (
        <EmptyState title="No bookings found" description="Try a different status filter." />
      )}

      {!isPending && !isError && bookings.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Code</th>
                <th className="py-2 pr-4 font-medium">Client</th>
                <th className="py-2 pr-4 font-medium">Provider</th>
                <th className="py-2 pr-4 font-medium">Date</th>
                <th className="py-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {bookings.map((b) => (
                <tr key={b.id}>
                  <td className="py-3 pr-4 text-ink">
                    <Link to={`/admin/bookings/${b.id}`} className="font-medium hover:underline">
                      {b.booking_code}
                    </Link>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{b.client?.full_name ?? '—'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{b.provider?.business_name ?? '—'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{b.scheduled_date}</td>
                  <td className="py-3">
                    <StatusBadge status={b.status} />
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
