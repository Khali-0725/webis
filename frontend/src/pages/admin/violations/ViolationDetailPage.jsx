import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { violationApi } from '@/services/api/admin/violationApi';
import { queryKeys } from '@/services/api/queryClient';

/**
 * Per §9.3: this page shows only the flagged message and the violation
 * record - never the surrounding conversation. There is nothing here to
 * "open the thread" from, deliberately.
 */
export default function ViolationDetailPage() {
  const { id } = useParams();
  const queryClient = useQueryClient();
  const [notice, setNotice] = useState(null);
  const [showSuspendConfirm, setShowSuspendConfirm] = useState(false);
  const [suspensionReason, setSuspensionReason] = useState('');
  const [suspensionBasis, setSuspensionBasis] = useState('');
  const [suspensionDays, setSuspensionDays] = useState(1);

  const { data: violation, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.violation(id),
    queryFn: () => violationApi.show(id),
  });

  const actionMutation = useMutation({
    mutationFn: ({ action, payload }) => violationApi.act(id, action, payload),
    onSuccess: (data) => {
      queryClient.setQueryData(queryKeys.admin.violation(id), data);
      queryClient.invalidateQueries({ queryKey: ['admin', 'violations'] });
      setShowSuspendConfirm(false);
      setNotice({ tone: 'success', message: `Moderation case ${data.admin_status_label.toLowerCase()}.` });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Action failed.' }),
  });

  if (isPending) return <LoadingState label="Loading violation…" />;
  if (isError) return <ErrorState description={error?.message} onRetry={() => refetch()} />;

  const closed = violation.admin_status !== 'open' && violation.admin_status !== 'reviewed';

  return (
    <Card title="Flagged Message Review">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <dl className="mb-5 grid gap-4 sm:grid-cols-2">
        <div>
          <dt className="text-xs uppercase tracking-wide text-ink-muted">User</dt>
          <dd className="text-ink">{violation.user?.full_name ?? 'Unknown'}</dd>
          <dd className="text-sm text-ink-muted">{violation.user?.email}</dd>
        </div>
        <div>
          <dt className="text-xs uppercase tracking-wide text-ink-muted">Status</dt>
          <dd>
            <Badge tone={closed ? 'neutral' : 'warning'}>{violation.admin_status_label}</Badge>
          </dd>
        </div>
        <div>
          <dt className="text-xs uppercase tracking-wide text-ink-muted">Category / Rule</dt>
          <dd className="text-ink">
            {violation.category_label} — {violation.matched_rule}
          </dd>
        </div>
        <div>
          <dt className="text-xs uppercase tracking-wide text-ink-muted">Flag severity / Automatic filter action</dt>
          <dd className="text-ink">
            {violation.severity} / {violation.action_taken}
          </dd>
        </div>
      </dl>

      <div className="mb-5 rounded-lg border border-line bg-slate-50 p-4">
        <p className="mb-1 text-xs uppercase tracking-wide text-ink-muted">Attempted message</p>
        <p className="text-sm text-ink">{violation.attempted_body}</p>
      </div>

      {!closed && (
        <div className="flex flex-wrap gap-2">
          <Button
            variant="outline"
            onClick={() => actionMutation.mutate({ action: 'dismiss' })}
            loading={actionMutation.isPending && actionMutation.variables?.action === 'dismiss'}
          >
            Dismiss
          </Button>
          <Button
            variant="subtle"
            onClick={() => actionMutation.mutate({ action: 'warn' })}
            loading={actionMutation.isPending && actionMutation.variables?.action === 'warn'}
          >
            Warn
          </Button>
          <Button
            variant="danger"
            onClick={() => {
              actionMutation.reset();
              setShowSuspendConfirm(true);
            }}
            loading={actionMutation.isPending && actionMutation.variables?.action === 'suspend'}
          >
            Suspend user
          </Button>
        </div>
      )}
      <ConfirmDialog
        open={showSuspendConfirm}
        onClose={() => setShowSuspendConfirm(false)}
        onConfirm={() => {
          if (!actionMutation.isPending) actionMutation.mutate({ action: 'suspend', payload: {
            reason: suspensionReason, basis: suspensionBasis, days: Number(suspensionDays),
          } });
        }}
        title={`Suspend ${violation.user?.full_name ?? 'this account'}?`}
        description="Record the reason and policy basis. This action revokes the current session and expires automatically."
        confirmLabel="Suspend account"
        tone="danger"
        loading={actionMutation.isPending}
        error={actionMutation.error?.message}
      >
        <div className="space-y-3">
          <label className="block text-sm font-medium text-ink">Violation basis
            <input value={suspensionBasis} onChange={(event) => setSuspensionBasis(event.target.value)} maxLength={120} className="mt-1 h-10 w-full rounded-lg border border-line bg-white px-3" placeholder="Policy or evidence reference" />
          </label>
          <label className="block text-sm font-medium text-ink">Reason
            <textarea value={suspensionReason} onChange={(event) => setSuspensionReason(event.target.value)} rows={3} maxLength={1000} className="mt-1 w-full rounded-lg border border-line bg-white px-3 py-2" placeholder="Explain the decision" />
          </label>
          <label className="block text-sm font-medium text-ink">Duration in days (maximum 15)
            <input type="number" min={1} max={15} value={suspensionDays} onChange={(event) => setSuspensionDays(event.target.value)} className="mt-1 h-10 w-full rounded-lg border border-line bg-white px-3" />
          </label>
        </div>
      </ConfirmDialog>
    </Card>
  );
}
