import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Select, Textarea } from '@/components/ui/Select';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { reportApi } from '@/services/api/reportApi';
import { fieldError } from '@/services/api/client';
import { REPORT_REASON_META } from '@/constants';

const STATUS_TONE = { open: 'warning', reviewing: 'info', resolved: 'success', dismissed: 'neutral' };

/**
 * The reporter's own reports - shared by client and provider (same
 * endpoint, same rules: editable and withdrawable only while still Open).
 */
export default function MyReportsPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [editing, setEditing] = useState(null);
  const [pendingWithdraw, setPendingWithdraw] = useState(null);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['reports', 'mine', { page }],
    queryFn: () => reportApi.list({ page }),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['reports', 'mine'] });

  const withdrawMutation = useMutation({
    mutationFn: reportApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingWithdraw(null);
      setNotice({ tone: 'success', message: 'Report withdrawn.' });
    },
  });

  const reports = data?.items ?? [];

  return (
    <Card title="My Reports">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {isPending && <LoadingState label="Loading reports…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && reports.length === 0 && (
        <EmptyState
          title="No reports filed"
          description="Reports you submit about a booking, a service or another user will appear here."
        />
      )}

      {!isPending && !isError && reports.length > 0 && (
        <ul className="divide-y divide-line">
          {reports.map((report) => (
            <li key={report.id} className="flex flex-wrap items-start justify-between gap-3 py-3">
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2">
                  <p className="font-medium text-ink">{report.reason_label}</p>
                  <Badge tone={STATUS_TONE[report.status] ?? 'neutral'}>{report.status_label}</Badge>
                </div>
                <p className="mt-0.5 text-xs text-ink-muted">
                  About {report.reportable_type} #{report.reportable_id} ·{' '}
                  {new Date(report.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })}
                </p>
                {report.details && <p className="mt-1 text-sm text-ink-muted">{report.details}</p>}
                {report.status !== 'open' && (
                  <p className="mt-1 text-xs text-ink-soft">
                    {report.status === 'reviewing'
                      ? 'An administrator is looking into this.'
                      : 'This report has been closed by an administrator.'}
                  </p>
                )}
              </div>
              {report.status === 'open' && (
                <div className="flex gap-2">
                  <Button size="sm" variant="subtle" onClick={() => setEditing(report)}>
                    Edit
                  </Button>
                  <Button size="sm" variant="ghost" className="text-red-600 hover:bg-red-50" onClick={() => setPendingWithdraw(report)}>
                    Withdraw
                  </Button>
                </div>
              )}
            </li>
          ))}
        </ul>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />

      {editing && (
        <ReportEditor
          report={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            invalidate();
            setNotice({ tone: 'success', message: 'Report updated.' });
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(pendingWithdraw)}
        onClose={() => {
          setPendingWithdraw(null);
          withdrawMutation.reset();
        }}
        onConfirm={() => withdrawMutation.mutate(pendingWithdraw.id)}
        title="Withdraw this report?"
        description={pendingWithdraw?.reason_label}
        confirmLabel="Withdraw report"
        tone="danger"
        loading={withdrawMutation.isPending}
        error={withdrawMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">It is removed from the administrators&apos; queue. You can file a new one later.</p>
      </ConfirmDialog>
    </Card>
  );
}

function ReportEditor({ report, onClose, onSaved }) {
  const [reason, setReason] = useState(report.reason);
  const [details, setDetails] = useState(report.details ?? '');

  const mutation = useMutation({
    mutationFn: () => reportApi.update(report.id, { reason, details: details || null }),
    onSuccess: onSaved,
  });

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit report"
      description={`About ${report.reportable_type} #${report.reportable_id}`}
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button onClick={() => mutation.mutate()} loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <div className="grid gap-4">
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <Select
          label="Reason"
          value={reason}
          onChange={(e) => setReason(e.target.value)}
          error={fieldError(err, 'reason')}
          options={Object.entries(REPORT_REASON_META).map(([value, meta]) => ({ value, label: meta.label }))}
        />
        <Textarea label="Details" rows={4} value={details} onChange={(e) => setDetails(e.target.value)} error={fieldError(err, 'details')} />
      </div>
    </Modal>
  );
}
