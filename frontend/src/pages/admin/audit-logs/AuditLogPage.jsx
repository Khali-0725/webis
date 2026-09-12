import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { auditLogApi } from '@/services/api/admin/auditLogApi';
import { queryKeys } from '@/services/api/queryClient';

export default function AuditLogPage() {
  const [action, setAction] = useState('');
  const [page, setPage] = useState(1);
  const params = { ...(action ? { action } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.auditLogs(params),
    queryFn: () => auditLogApi.list(params),
  });

  const logs = data?.items ?? [];

  return (
    <Card title="Audit Logs">
      <div className="mb-4">
        <Input
          placeholder="Filter by action (e.g. suspend, verification)"
          value={action}
          onChange={(e) => {
            setAction(e.target.value);
            setPage(1);
          }}
        />
      </div>

      {isPending && <LoadingState label="Loading audit logs…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && logs.length === 0 && (
        <EmptyState title="No matching entries" description="Try a different filter." />
      )}

      {!isPending && !isError && logs.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">When</th>
                <th className="py-2 pr-4 font-medium">Actor</th>
                <th className="py-2 pr-4 font-medium">Action</th>
                <th className="py-2 font-medium">Subject</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {logs.map((l) => (
                <tr key={l.id}>
                  <td className="py-3 pr-4 text-ink-muted">{new Date(l.created_at).toLocaleString()}</td>
                  <td className="py-3 pr-4 text-ink">{l.actor?.full_name ?? 'System'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{l.action}</td>
                  <td className="py-3 text-ink-muted">
                    {l.auditable_type ? `${l.auditable_type} #${l.auditable_id}` : '—'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />
    </Card>
  );
}
