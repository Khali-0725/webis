import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Modal } from '@/components/ui/Modal';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { AdminRecordFilters } from '@/components/ui/AdminRecordFilters';
import { BulkArchiveControls } from '@/components/ui/BulkArchiveControls';
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
  const [recordFilters, setRecordFilters] = useState({ sort: 'newest', start_date: '', end_date: '' });
  const [notice, setNotice] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const [viewing, setViewing] = useState(null);
  const [pendingDecision, setPendingDecision] = useState(null);
  const [handlingNotes, setHandlingNotes] = useState('');
  const [selectedIds, setSelectedIds] = useState([]);
  const params = { ...(status ? { status } : {}), ...(trashed ? { trashed } : {}), ...Object.fromEntries(Object.entries(recordFilters).filter(([, v]) => v)), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.reports(params),
    queryFn: () => adminReportApi.list(params),
  });

  const reports = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'reports'] });

  const resolveMutation = useMutation({
    mutationFn: ({ id, status: next, handling_notes }) => adminReportApi.resolve(id, { status: next, handling_notes }),
    onSuccess: () => {
      invalidate();
      setPendingDecision(null);
      setHandlingNotes('');
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

  const chooseDecision = (report, decision) => {
    setPendingDecision({ report, decision });
    setHandlingNotes('');
    resolveMutation.reset();
  };

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

      <AdminRecordFilters value={recordFilters} inTrash={trashed === 'only'} onChange={(patch) => { setRecordFilters({ ...recordFilters, ...patch }); setPage(1); }} />

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
          <BulkArchiveControls resource="reports" rows={reports} selectedIds={selectedIds} onSelectionChange={setSelectedIds} onArchived={(count) => { invalidate(); setNotice({ tone: 'success', message: `${count} report${count === 1 ? '' : 's'} archived.` }); }} />
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-3"><span className="sr-only">Select</span></th>
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
                  <td className="py-3 pr-3">{!r.deleted_at && <input type="checkbox" aria-label={`Select report ${r.id}`} checked={selectedIds.includes(r.id)} onChange={(event) => setSelectedIds(event.target.checked ? [...selectedIds, r.id] : selectedIds.filter((id) => id !== r.id))} />}</td>
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
                        <>
                          <Button size="sm" variant="outline" onClick={() => setViewing(r)}>View details</Button>
                          {(r.status === 'open' || r.status === 'reviewing') && (
                          <>
                            {r.status === 'open' && <Button size="sm" variant="outline" onClick={() => chooseDecision(r, 'reviewing')}>Acknowledge</Button>}
                            <Button size="sm" variant="success" onClick={() => chooseDecision(r, 'resolved')}>
                              Resolve
                            </Button>
                            <Button size="sm" variant="subtle" onClick={() => chooseDecision(r, 'dismissed')}>
                              Dismiss
                            </Button>
                          </>
                          )}
                        </>
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

      <Modal open={Boolean(viewing)} onClose={() => setViewing(null)} title="Report details" description={viewing ? `Report #${viewing.id}` : undefined} size="sm">
        {viewing && <div className="space-y-3 text-sm text-ink">
          <p><strong>Reporter:</strong> {viewing.reporter?.full_name ?? 'Unknown'}</p>
          <p><strong>Record:</strong> {viewing.reportable_type} #{viewing.reportable_id}</p>
          <p><strong>Reason:</strong> {viewing.reason_label}</p>
          <p className="whitespace-pre-wrap"><strong>Explanation:</strong> {viewing.details}</p>
          <p><strong>Status:</strong> {viewing.status_label}</p>
          {viewing.handling_notes && <p className="whitespace-pre-wrap"><strong>Admin notes:</strong> {viewing.handling_notes}</p>}
        </div>}
      </Modal>

      <ConfirmDialog
        open={Boolean(pendingDecision)}
        onClose={() => { setPendingDecision(null); setHandlingNotes(''); resolveMutation.reset(); }}
        onConfirm={() => resolveMutation.mutate({ id: pendingDecision.report.id, status: pendingDecision.decision, handling_notes: handlingNotes.trim() })}
        title={pendingDecision?.decision === 'reviewing' ? 'Acknowledge this report?' : pendingDecision?.decision === 'resolved' ? 'Resolve this report?' : 'Dismiss this report?'}
        description={pendingDecision ? `${pendingDecision.report.reason_label} · ${pendingDecision.report.reportable_type} #${pendingDecision.report.reportable_id}` : undefined}
        confirmLabel={pendingDecision?.decision === 'reviewing' ? 'Mark under review' : pendingDecision?.decision === 'resolved' ? 'Resolve report' : 'Dismiss report'}
        disabled={handlingNotes.trim().length < 10}
        loading={resolveMutation.isPending}
        error={resolveMutation.error?.message}
      >
        <label className="block text-sm font-medium text-ink">Handling notes
          <textarea required minLength={10} maxLength={2000} rows={3} value={handlingNotes} onChange={(event) => setHandlingNotes(event.target.value)} placeholder="Record the basis for this decision" className="mt-1 block w-full rounded-lg border border-line bg-white px-3 py-2 text-sm" />
        </label>
        <p className="mt-2 text-xs text-ink-muted">The reporter receives only the updated status, not these internal notes.</p>
      </ConfirmDialog>

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
