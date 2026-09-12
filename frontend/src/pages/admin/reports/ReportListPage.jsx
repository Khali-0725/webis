import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { adminReportApi } from '@/services/api/admin/reportApi';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_TONE = { open: 'warning', reviewing: 'info', resolved: 'success', dismissed: 'neutral' };

export default function ReportListPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.reports({ page }),
    queryFn: () => adminReportApi.list({ page }),
  });

  const reports = data?.items ?? [];

  const resolveMutation = useMutation({
    mutationFn: ({ id, status }) => adminReportApi.resolve(id, { status }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'reports'] });
      setNotice({ tone: 'success', message: 'Report updated.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to update report.' }),
  });

  return (
    <Card title="Reports">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {isPending && <LoadingState label="Loading reports…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && reports.length === 0 && (
        <EmptyState title="No open reports" description="User-submitted reports will appear here." />
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
                  <td className="py-3 pr-4 text-ink-muted">{r.reason_label}</td>
                  <td className="py-3 pr-4">
                    <Badge tone={STATUS_TONE[r.status] ?? 'neutral'}>{r.status_label}</Badge>
                  </td>
                  <td className="py-3">
                    {(r.status === 'open' || r.status === 'reviewing') && (
                      <div className="flex gap-2">
                        <Button
                          size="sm"
                          variant="success"
                          onClick={() => resolveMutation.mutate({ id: r.id, status: 'resolved' })}
                        >
                          Resolve
                        </Button>
                        <Button
                          size="sm"
                          variant="subtle"
                          onClick={() => resolveMutation.mutate({ id: r.id, status: 'dismissed' })}
                        >
                          Dismiss
                        </Button>
                      </div>
                    )}
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
