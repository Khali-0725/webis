import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { paymentApi } from '@/services/api/paymentApi';

const STATUS_TONE = {
  pending: 'neutral',
  proof_submitted: 'warning',
  verified: 'success',
  rejected: 'danger',
  refunded: 'info',
};

export default function AdminPaymentListPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [trashed, setTrashed] = useState('');
  const [notice, setNotice] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const params = { ...(trashed ? { trashed } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'payments', params],
    queryFn: () => paymentApi.list(params),
  });

  const payments = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'payments'] });

  const restoreMutation = useMutation({
    mutationFn: paymentApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Payment restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore payment.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: paymentApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Payment moved to trash.' });
    },
  });

  return (
    <Card title="All Payments" action={<TrashToggle value={trashed} onChange={(v) => { setTrashed(v); setPage(1); }} />}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {isPending && <LoadingState label="Loading payments…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && payments.length === 0 && (
        <EmptyState
          title={trashed ? 'Trash is empty' : 'No payments yet'}
          description={trashed ? 'Deleted payments will appear here until restored.' : 'Payments will appear here once bookings are made.'}
        />
      )}

      {!isPending && !isError && payments.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Booking</th>
                <th className="py-2 pr-4 font-medium">Amount</th>
                <th className="py-2 pr-4 font-medium">Reference</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {payments.map((p) => (
                <tr key={p.id}>
                  <td className="py-3 pr-4 text-ink">
                    <Link to={`/admin/bookings/${p.booking_id}`} className="font-medium hover:underline">
                      #{p.booking_id}
                    </Link>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {p.currency} {p.amount}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{p.reference_number ?? '—'}</td>
                  <td className="py-3 pr-4">
                    <span className="inline-flex flex-wrap items-center gap-1.5">
                      <Badge tone={STATUS_TONE[p.status] ?? 'neutral'}>{p.status_label}</Badge>
                      <DeletedBadge deletedAt={p.deleted_at} />
                    </span>
                  </td>
                  <td className="py-3">
                    <RowActions
                      record={p}
                      busy={restoreMutation.isPending && restoreMutation.variables === p.id}
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
        title="Delete this payment record?"
        description={pendingDelete ? `Booking #${pendingDelete.booking_id} · ${pendingDelete.currency} ${pendingDelete.amount}` : undefined}
        confirmLabel="Delete payment"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          Financial records are never removed outright - this moves it to the trash, out of the ledger
          and reports, and the action is written to the audit log. You can restore it here.
        </p>
      </ConfirmDialog>
    </Card>
  );
}
