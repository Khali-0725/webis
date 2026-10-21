import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { StatusBadge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { bookingApi } from '@/services/api/bookingApi';
import { publicApi } from '@/services/api/publicApi';
import { providerApi } from '@/services/api/admin/providerApi';
import { BOOKING_STATUS } from '@/constants';

const STATUS_OPTIONS = ['', ...Object.values(BOOKING_STATUS)];

export default function AdminBookingListPage() {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState('');
  const [providerId, setProviderId] = useState('');
  const [providerSearch, setProviderSearch] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [sort, setSort] = useState('newest');
  const [trashed, setTrashed] = useState('');
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const params = {
    ...(status ? { status } : {}), ...(trashed ? { trashed } : {}),
    ...(providerId ? { provider_id: providerId } : {}),
    ...(categoryId ? { category_id: categoryId } : {}),
    ...(startDate ? { start_date: startDate } : {}),
    ...(endDate ? { end_date: endDate } : {}), sort, page,
  };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'bookings', params],
    queryFn: () => bookingApi.list(params),
  });
  const { data: categories = [] } = useQuery({
    queryKey: ['public', 'service-categories'],
    queryFn: publicApi.listCategories,
  });
  const { data: providerMatches, isError: providerSearchError } = useQuery({
    queryKey: ['admin', 'booking-provider-options', providerSearch],
    queryFn: () => providerApi.list({ search: providerSearch, per_page: 20 }),
  });

  const bookings = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'bookings'] });

  const restoreMutation = useMutation({
    mutationFn: bookingApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Booking restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore booking.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: bookingApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Booking moved to trash.' });
    },
  });

  return (
    <Card title="All Bookings" action={<TrashToggle value={trashed} onChange={(v) => { setTrashed(v); setPage(1); }} />}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <div className="mb-4 flex flex-wrap gap-2">
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
        <div className="flex min-w-48 flex-col gap-1">
          <input aria-label="Search providers" placeholder="Search provider name" value={providerSearch} onChange={(e) => { setProviderSearch(e.target.value); setProviderId(''); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-3 text-sm" />
          <select aria-label="Filter by provider" value={providerId} onChange={(e) => { setProviderId(e.target.value); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-3 text-sm">
            <option value="">All providers</option>
            {(providerMatches?.items ?? []).map((provider) => <option key={provider.id} value={provider.id}>{provider.business_name || provider.owner?.full_name || `Provider ${provider.id}`}</option>)}
          </select>
          {providerSearchError && <span className="text-xs text-red-700">Provider search failed. Try again.</span>}
        </div>
        <select aria-label="Filter by category" value={categoryId} onChange={(e) => { setCategoryId(e.target.value); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-3 text-sm">
          <option value="">All categories</option>
          {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
        </select>
        <label className="flex items-center gap-1 text-xs text-ink-muted">From <input type="date" value={startDate} max={endDate || undefined} onChange={(e) => { setStartDate(e.target.value); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-2 text-sm" /></label>
        <label className="flex items-center gap-1 text-xs text-ink-muted">To <input type="date" value={endDate} min={startDate || undefined} onChange={(e) => { setEndDate(e.target.value); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-2 text-sm" /></label>
        <select aria-label="Sort bookings" value={sort} onChange={(e) => { setSort(e.target.value); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-3 text-sm"><option value="newest">Newest first</option><option value="oldest">Oldest first</option></select>
      </div>

      {isPending && <LoadingState label="Loading bookings…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && bookings.length === 0 && (
        <EmptyState
          title={trashed ? 'Trash is empty' : 'No bookings found'}
          description={trashed ? 'Deleted bookings will appear here until restored.' : 'Try a different status filter.'}
        />
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
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {bookings.map((b) => (
                <tr key={b.id}>
                  <td className="py-3 pr-4 text-ink">
                    {b.deleted_at ? (
                      <span className="font-medium">{b.booking_code}</span>
                    ) : (
                      <Link to={`/admin/bookings/${b.id}`} className="font-medium hover:underline">
                        {b.booking_code}
                      </Link>
                    )}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{b.client?.full_name ?? '—'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{b.provider?.business_name ?? '—'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{b.scheduled_date}</td>
                  <td className="py-3 pr-4">
                    <span className="inline-flex flex-wrap items-center gap-1.5">
                      <StatusBadge status={b.status} />
                      <DeletedBadge deletedAt={b.deleted_at} />
                    </span>
                  </td>
                  <td className="py-3">
                    <RowActions
                      record={b}
                      busy={restoreMutation.isPending && restoreMutation.variables === b.id}
                      onDelete={setPendingDelete}
                      onRestore={(row) => restoreMutation.mutate(row.id)}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />

      <ConfirmDialog
        open={Boolean(pendingDelete)}
        onClose={() => {
          setPendingDelete(null);
          deleteMutation.reset();
        }}
        onConfirm={() => deleteMutation.mutate(pendingDelete.id)}
        title="Delete this booking?"
        description={pendingDelete?.booking_code}
        confirmLabel="Delete booking"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          The booking is moved to the trash and hidden from both the client and the provider. Its
          payment, status history and location stay on record, and you can restore it here.
        </p>
      </ConfirmDialog>
    </Card>
  );
}
