import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { violationApi } from '@/services/api/admin/violationApi';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_OPTIONS = ['open', 'reviewed', 'dismissed', 'actioned'];
const STATUS_TONE = { open: 'warning', reviewed: 'info', dismissed: 'neutral', actioned: 'danger' };

export default function ViolationListPage() {
  const [status, setStatus] = useState('open');
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.violations({ admin_status: status, page }),
    queryFn: () => violationApi.list({ admin_status: status, page }),
  });

  const violations = data?.items ?? [];

  return (
    <Card title="Chat Violations">
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

      {isPending && <LoadingState label="Loading violations…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && violations.length === 0 && (
        <EmptyState title="Nothing here" description="No violations match this status." />
      )}

      {!isPending && !isError && violations.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">User</th>
                <th className="py-2 pr-4 font-medium">Category</th>
                <th className="py-2 pr-4 font-medium">Severity</th>
                <th className="py-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {violations.map((v) => (
                <tr key={v.id}>
                  <td className="py-3 pr-4 text-ink">
                    <Link to={`/admin/violations/${v.id}`} className="font-medium hover:underline">
                      {v.user?.full_name ?? 'Unknown user'}
                    </Link>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{v.category_label}</td>
                  <td className="py-3 pr-4 text-ink-muted">{v.severity}</td>
                  <td className="py-3">
                    <Badge tone={STATUS_TONE[v.admin_status] ?? 'neutral'}>{v.admin_status_label}</Badge>
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
