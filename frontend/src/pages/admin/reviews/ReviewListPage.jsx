import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { AdminRecordFilters } from '@/components/ui/AdminRecordFilters';
import { BulkArchiveControls } from '@/components/ui/BulkArchiveControls';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { reviewApi } from '@/services/api/reviewApi';

export default function AdminReviewListPage() {
  const [trashed, setTrashed] = useState('');
  const [sort, setSort] = useState('newest');
  const [dates, setDates] = useState({ start_date: '', end_date: '' });
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState(null);
  const [action, setAction] = useState('hide');
  const [reason, setReason] = useState('');
  const [selectedIds, setSelectedIds] = useState([]);
  const queryClient = useQueryClient();
  const params = { ...(trashed ? { trashed } : {}), sort, ...Object.fromEntries(Object.entries(dates).filter(([, v]) => v)), page };
  const { data, isPending, isError, error, refetch } = useQuery({ queryKey: ['admin', 'reviews', params], queryFn: () => reviewApi.adminList(params) });
  const mutation = useMutation({ mutationFn: () => {
    if (action === 'restore') return reviewApi.adminRestore(selected.id);
    if (action === 'archive') return reviewApi.adminArchive(selected.id, reason);
    return reviewApi.adminVisibility(selected.id, action === 'show', reason);
  }, onSuccess: () => { setSelected(null); setReason(''); queryClient.invalidateQueries({ queryKey: ['admin', 'reviews'] }); } });
  const select = (review, nextAction) => { setSelected(review); setAction(nextAction); setReason(''); mutation.reset(); };

  return <Card title="Review Records">
    <div className="mb-4 print:hidden"><select aria-label="Review records" value={trashed} onChange={(e) => { setTrashed(e.target.value); setPage(1); }} className="h-10 rounded-lg border border-line bg-white px-3 text-sm"><option value="">Active</option><option value="only">Archived</option><option value="with">All</option></select></div>
    <AdminRecordFilters value={{ sort, ...dates }} inTrash={trashed === 'only'} onChange={(patch) => { if (patch.sort) setSort(patch.sort); else setDates({ ...dates, ...patch }); setPage(1); }} />
    {isPending && <LoadingState label="Loading reviews…" />}
    {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}
    {!isPending && !isError && <BulkArchiveControls resource="reviews" rows={data.items} selectedIds={selectedIds} onSelectionChange={setSelectedIds} onArchived={() => queryClient.invalidateQueries({ queryKey: ['admin', 'reviews'] })} />}
    {!isPending && !isError && data.items.length === 0 && <EmptyState title="No review records" description="No reviews match this view." />}
    {!isPending && !isError && data.items.length > 0 && <div className="space-y-3">{data.items.map((review) => <div key={review.id} className="rounded-lg border border-line p-4">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div className="flex items-start gap-3">
          {!review.deleted_at && <input type="checkbox" aria-label={`Select review ${review.id}`} checked={selectedIds.includes(review.id)} onChange={(event) => setSelectedIds(event.target.checked ? [...selectedIds, review.id] : selectedIds.filter((id) => id !== review.id))} />}
          <div><p className="font-semibold text-ink">{review.service_title || `Booking #${review.booking_id}`} · {review.rating}/5</p><p className="text-xs text-ink-muted">{review.client?.full_name} · {review.created_at?.slice(0, 10)} · {review.deleted_at ? 'Archived' : review.is_visible ? 'Public' : 'Hidden'}</p></div>
        </div>
        <div className="flex flex-wrap gap-2">{review.deleted_at ? <Button size="sm" variant="outline" onClick={() => select(review, 'restore')}>Restore hidden</Button> : <><Button size="sm" variant="outline" onClick={() => select(review, review.is_visible ? 'hide' : 'show')}>{review.is_visible ? 'Hide' : 'Show'}</Button><Button size="sm" variant="danger" onClick={() => select(review, 'archive')}>Archive</Button></>}</div>
      </div>
      {review.comment && <p className="mt-2 text-sm text-ink-muted">{review.comment}</p>}
    </div>)}</div>}
    <Pagination meta={data?.meta} onChange={setPage} />
    <ConfirmDialog open={Boolean(selected)} onClose={() => setSelected(null)} onConfirm={() => { if (action === 'restore' || reason.trim().length >= 5) mutation.mutate(); }} title={`${action[0]?.toUpperCase()}${action.slice(1)} review?`} description={selected?.service_title} confirmLabel={action === 'restore' ? 'Restore hidden' : `${action[0]?.toUpperCase()}${action.slice(1)} review`} tone={action === 'archive' ? 'danger' : 'primary'} loading={mutation.isPending} error={mutation.error?.message}>
      {action !== 'restore' && <label className="block text-sm text-ink">Moderation reason<textarea required minLength={5} maxLength={500} rows={3} value={reason} onChange={(e) => setReason(e.target.value)} className="mt-1 block w-full rounded-lg border border-line p-2" /></label>}
      {action === 'restore' && <p className="text-sm text-ink-muted">The review will return as hidden until an administrator chooses to show it.</p>}
    </ConfirmDialog>
  </Card>;
}
