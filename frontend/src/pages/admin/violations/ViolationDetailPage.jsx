import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
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

  const { data: violation, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.violation(id),
    queryFn: () => violationApi.show(id),
  });

  const actionMutation = useMutation({
    mutationFn: (action) => violationApi.act(id, action),
    onSuccess: (data) => {
      queryClient.setQueryData(queryKeys.admin.violation(id), data);
      queryClient.invalidateQueries({ queryKey: ['admin', 'violations'] });
      setNotice({ tone: 'success', message: `Violation ${data.admin_status_label.toLowerCase()}.` });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Action failed.' }),
  });

  if (isPending) return <LoadingState label="Loading violation…" />;
  if (isError) return <ErrorState description={error?.message} onRetry={() => refetch()} />;

  const closed = violation.admin_status !== 'open' && violation.admin_status !== 'reviewed';

  return (
    <Card title="Flagged Message">
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
          <dt className="text-xs uppercase tracking-wide text-ink-muted">Severity / Action taken</dt>
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
            onClick={() => actionMutation.mutate('dismiss')}
            loading={actionMutation.isPending && actionMutation.variables === 'dismiss'}
          >
            Dismiss
          </Button>
          <Button
            variant="subtle"
            onClick={() => actionMutation.mutate('warn')}
            loading={actionMutation.isPending && actionMutation.variables === 'warn'}
          >
            Warn
          </Button>
          <Button
            variant="danger"
            onClick={() => {
              if (confirm('This will suspend the user account. Continue?')) actionMutation.mutate('suspend');
            }}
            loading={actionMutation.isPending && actionMutation.variables === 'suspend'}
          >
            Suspend user
          </Button>
        </div>
      )}
    </Card>
  );
}
