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
import { violationApi } from '@/services/api/admin/violationApi';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_OPTIONS = ['open', 'reviewed', 'dismissed', 'actioned'];
const STATUS_TONE = { open: 'warning', reviewed: 'info', dismissed: 'neutral', actioned: 'danger' };

export default function ViolationListPage() {
  const queryClient = useQueryClient();
  const [status, setStatus] = useState('open');
  const [trashed, setTrashed] = useState('');
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const inTrash = trashed === 'only';
  // The trash shows every deleted violation regardless of its status.
  const params = { ...(inTrash ? { trashed } : { admin_status: status }), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.violations(params),
    queryFn: () => violationApi.list(params),
  });

  const violations = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'violations'] });

  const restoreMutation = useMutation({
    mutationFn: violationApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Violation restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore violation.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: violationApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Violation moved to trash.' });
    },
  });

  return (
    <Card title="Chat Violations" action={<TrashToggle value={trashed} onChange={(v) => { setTrashed(v); setPage(1); }} />}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {!inTrash && (
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
                {s[0].toUpperCase() + s.slice(1)}
              </option>
            ))}
          </select>
        </div>
      )}

      {isPending && <LoadingState label="Loading violations…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && violations.length === 0 && (
        <EmptyState
          title={inTrash ? 'Trash is empty' : 'Nothing here'}
          description={inTrash ? 'Deleted violations will appear here until restored.' : 'No violations match this status.'}
        />
      )}

      {!isPending && !isError && violations.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">User</th>
                <th className="py-2 pr-4 font-medium">Category</th>
                <th className="py-2 pr-4 font-medium">Severity</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {violations.map((v) => (
                <tr key={v.id}>
                  <td className="py-3 pr-4 text-ink">
                    {v.deleted_at ? (
                      <span className="font-medium">{v.user?.full_name ?? 'Unknown user'}</span>
                    ) : (
                      <Link to={`/admin/violations/${v.id}`} className="font-medium hover:underline">
                        {v.user?.full_name ?? 'Unknown user'}
                      </Link>
                    )}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{v.category_label}</td>
                  <td className="py-3 pr-4 text-ink-muted">{v.severity}</td>
                  <td className="py-3 pr-4">
                    <span className="inline-flex flex-wrap items-center gap-1.5">
                      <Badge tone={STATUS_TONE[v.admin_status] ?? 'neutral'}>{v.admin_status_label}</Badge>
                      <DeletedBadge deletedAt={v.deleted_at} />
                    </span>
                  </td>
                  <td className="py-3">
                    <RowActions
                      record={v}
                      busy={restoreMutation.isPending && restoreMutation.variables === v.id}
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
        title="Delete this violation?"
        description={pendingDelete ? `${pendingDelete.user?.full_name ?? 'Unknown user'} - ${pendingDelete.category_label}` : undefined}
        confirmLabel="Delete violation"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          It leaves the moderation queue but stays on record in the trash and the audit log. Use Dismiss
          on the violation itself if it was reviewed and needs no action.
        </p>
      </ConfirmDialog>
    </Card>
  );
}
