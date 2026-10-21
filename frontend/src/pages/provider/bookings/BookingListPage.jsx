import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { StatusBadge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { bookingApi } from '@/services/api/bookingApi';
import { publicApi } from '@/services/api/publicApi';
import { queryKeys } from '@/services/api/queryClient';
import { POLL_SLOW } from '@/services/realtime/echo';
import { BOOKING_STATUS_META } from '@/constants';

export default function BookingListPage() {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [sort, setSort] = useState('newest');
  const [page, setPage] = useState(1);
  const [bookingToAccept, setBookingToAccept] = useState(null);
  const { data: categories = [] } = useQuery({ queryKey: queryKeys.public.categories, queryFn: publicApi.listCategories });
  const filters = {
    ...(status ? { status } : {}),
    ...(categoryId ? { category_id: categoryId } : {}),
    ...(startDate ? { start_date: startDate } : {}),
    ...(endDate ? { end_date: endDate } : {}),
    sort,
    page,
  };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.bookings.list(filters),
    queryFn: () => bookingApi.list(filters),
    refetchInterval: POLL_SLOW,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['bookings'] });

  const acceptMutation = useMutation({
    mutationFn: bookingApi.accept,
    onSuccess: () => {
      setBookingToAccept(null);
      invalidate();
    },
  });
  const rejectMutation = useMutation({ mutationFn: (id) => bookingApi.reject(id), onSuccess: invalidate });

  return (
    <Card title="Bookings">
      <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <label htmlFor="status-filter" className="flex flex-col gap-1 text-sm text-ink-muted">
          Status
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
        </label>
        <label htmlFor="category-filter" className="flex flex-col gap-1 text-sm text-ink-muted">
          Category
          <select
            id="category-filter"
            value={categoryId}
            onChange={(event) => {
              setCategoryId(event.target.value);
              setPage(1);
            }}
            className="h-9 rounded-lg border border-line bg-white px-2 text-sm text-ink"
          >
            <option value="">All categories</option>
            {categories.map((category) => (
              <option key={category.id} value={category.id}>{category.name}</option>
            ))}
          </select>
        </label>
        <label htmlFor="start-date-filter" className="flex flex-col gap-1 text-sm text-ink-muted">
          From date
          <input
            id="start-date-filter"
            type="date"
            value={startDate}
            max={endDate || undefined}
            onChange={(event) => {
              setStartDate(event.target.value);
              setPage(1);
            }}
            className="h-9 rounded-lg border border-line bg-white px-2 text-sm text-ink"
          />
        </label>
        <label htmlFor="end-date-filter" className="flex flex-col gap-1 text-sm text-ink-muted">
          To date
          <input
            id="end-date-filter"
            type="date"
            value={endDate}
            min={startDate || undefined}
            onChange={(event) => {
              setEndDate(event.target.value);
              setPage(1);
            }}
            className="h-9 rounded-lg border border-line bg-white px-2 text-sm text-ink"
          />
        </label>
        <label htmlFor="sort-filter" className="flex flex-col gap-1 text-sm text-ink-muted">
          Sort by date
          <select
            id="sort-filter"
            value={sort}
            onChange={(event) => {
              setSort(event.target.value);
              setPage(1);
            }}
            className="h-9 rounded-lg border border-line bg-white px-2 text-sm text-ink"
          >
            <option value="newest">Newest first</option>
            <option value="oldest">Oldest first</option>
          </select>
        </label>
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
                            onClick={() => {
                              acceptMutation.reset();
                              setBookingToAccept(booking);
                            }}
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
      <ConfirmDialog
        open={Boolean(bookingToAccept)}
        onClose={() => setBookingToAccept(null)}
        onConfirm={() => {
          if (bookingToAccept && !acceptMutation.isPending) acceptMutation.mutate(bookingToAccept.id);
        }}
        title="Are you sure you want to accept this booking?"
        description={`${bookingToAccept?.client?.full_name ?? 'Client'} · ${bookingToAccept?.service?.title ?? 'Service'}`}
        confirmLabel="Accept Booking"
        loading={acceptMutation.isPending}
        error={acceptMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          {bookingToAccept?.scheduled_date} at {bookingToAccept?.scheduled_start_time?.slice(0, 5)}.
          The service address becomes available after acceptance.
        </p>
      </ConfirmDialog>
    </Card>
  );
}
