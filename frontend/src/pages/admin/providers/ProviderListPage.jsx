import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Card } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { providerApi } from '@/services/api/admin/providerApi';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_OPTIONS = ['', 'pending', 'approved', 'rejected'];
const STATUS_TONE = { pending: 'warning', approved: 'success', rejected: 'danger' };

export default function ProviderListPage() {
  const [filters, setFilters] = useState({ verification_status: '', search: '' });
  const [page, setPage] = useState(1);
  const params = { ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)), page };

  const updateFilter = (patch) => {
    setFilters({ ...filters, ...patch });
    setPage(1);
  };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.providers(params),
    queryFn: () => providerApi.list(params),
  });

  const providers = data?.items ?? [];

  return (
    <Card title="Providers">
      <div className="mb-4 grid gap-3 sm:grid-cols-2">
        <select
          aria-label="Filter by verification status"
          className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
          value={filters.verification_status}
          onChange={(e) => updateFilter({ verification_status: e.target.value })}
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s ? s[0].toUpperCase() + s.slice(1) : 'All verification statuses'}
            </option>
          ))}
        </select>
        <Input
          placeholder="Search business name"
          value={filters.search}
          onChange={(e) => updateFilter({ search: e.target.value })}
        />
      </div>

      {isPending && <LoadingState label="Loading providers…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && providers.length === 0 && (
        <EmptyState title="No providers found" description="Try adjusting the filters above." />
      )}

      {!isPending && !isError && providers.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Business</th>
                <th className="py-2 pr-4 font-medium">Owner</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 pr-4 font-medium">Rating</th>
                <th className="py-2 font-medium">Completed</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {providers.map((p) => (
                <tr key={p.id}>
                  <td className="py-3 pr-4 text-ink">
                    <Link to={`/admin/providers/${p.id}`} className="font-medium hover:underline">
                      {p.business_name}
                    </Link>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{p.owner?.full_name ?? '—'}</td>
                  <td className="py-3 pr-4">
                    <Badge tone={STATUS_TONE[p.verification_status] ?? 'neutral'}>
                      {p.verification_status_label}
                    </Badge>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {p.rating_avg ? `${p.rating_avg} (${p.rating_count})` : '—'}
                  </td>
                  <td className="py-3 text-ink-muted">{p.completed_bookings_count}</td>
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
