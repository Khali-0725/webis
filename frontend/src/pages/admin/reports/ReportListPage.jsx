import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { adminReportApi } from '@/services/api/admin/reportApi';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_TONE = { open: 'warning', reviewing: 'info', resolved: 'success', dismissed: 'neutral' };
const STATUS_OPTIONS = [
  { value: '', label: 'Open queue' },
  { value: 'open', label: 'Open' },
  { value: 'reviewing', label: 'Under review' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'dismissed', label: 'Dismissed' },
];

export default function ReportListPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState('');
  const [trashed, setTrashed] = useState('');
  const [notice, setNotice] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const params = { ...(status ? { status } : {}), ...(trashed ? { trashed } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.reports(params),
    queryFn: () => adminReportApi.list(params),
  });

  const reports = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'reports'] });

  const resolveMutation = useMutation({
    mutationFn: ({ id, status: next }) => adminReportApi.resolve(id, { status: next }),
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Report updated.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to update report.' }),
  });

  const restoreMutation = useMutation({
    mutationFn: adminReportApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Report restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore report.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: adminReportApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Report moved to trash.' });
    },
  });

  return (
    <Card title="Reports" action={<TrashToggle value={trashed} onChange={(v) => { setTrashed(v); setPage(1); }} />}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

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
          {STATUS_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>

      {isPending && <LoadingState label="Loading reports…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && reports.length === 0 && (
        <EmptyState
          title={trashed ? 'Trash is empty' : 'No reports here'}
          description={trashed ? 'Deleted reports will appear here until restored.' : 'User-submitted reports will appear here.'}
        />
      )}

      {!isPending && !isError && reports.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Reporter</th>
                <th className="py-2 pr-4 font-medium">Against</th>
                <th className="py-2 pr-4 font-medium">Reason</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {reports.map((r) => (
                <tr key={r.id}>
                  <td className="py-3 pr-4 text-ink">{r.reporter?.full_name ?? 'Unknown'}</td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {r.reportable_type} #{r.reportable_id}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">
                    <span className="block">{r.reason_label}</span>
                    {r.details && <span className="block max-w-xs truncate text-xs text-ink-soft">{r.details}</span>}
                  </td>
                  <td className="py-3 pr-4">
                    <span className="inline-flex flex-wrap items-center gap-1.5">
                      <Badge tone={STATUS_TONE[r.status] ?? 'neutral'}>{r.status_label}</Badge>
                      <DeletedBadge deletedAt={r.deleted_at} />
                    </span>
                  </td>
                  <td className="py-3">
                    <RowActions
                      record={r}
                      busy={restoreMutation.isPending && restoreMutation.variables === r.id}
                      onDelete={setPendingDelete}
                      onRestore={(row) => restoreMutation.mutate(row.id)}
                      extra={
                        (r.status === 'open' || r.status === 'reviewing') && (
                          <>
                            <Button size="sm" variant="success" onClick={() => resolveMutation.mutate({ id: r.id, status: 'resolved' })}>
                              Resolve
                            </Button>
                            <Button size="sm" variant="subtle" onClick={() => resolveMutation.mutate({ id: r.id, status: 'dismissed' })}>
                              Dismiss
                            </Button>
                          </>
                        )
                      }
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
        title="Delete this report?"
        description={pendingDelete ? `${pendingDelete.reason_label} - by ${pendingDelete.reporter?.full_name ?? 'unknown'}` : undefined}
        confirmLabel="Delete report"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          It leaves the queue but stays on record in the trash and the audit log. Use Dismiss instead if
          the report was reviewed and simply needs no action.
        </p>
      </ConfirmDialog>
    </Card>
  );
}
