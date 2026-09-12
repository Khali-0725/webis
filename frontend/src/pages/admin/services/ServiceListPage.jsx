import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { adminServiceApi } from '@/services/api/admin/serviceApi';
import { queryKeys } from '@/services/api/queryClient';

export default function ServiceListPage() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const params = { ...(search ? { search } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.services(params),
    queryFn: () => adminServiceApi.list(params),
  });

  const services = data?.items ?? [];

  const toggleMutation = useMutation({
    mutationFn: adminServiceApi.toggle,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin', 'services'] }),
  });

  return (
    <Card title="Services">
      <div className="mb-4">
        <Input
          placeholder="Search by title"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
        />
      </div>

      {isPending && <LoadingState label="Loading services…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && services.length === 0 && (
        <EmptyState title="No services found" description="Try a different search term." />
      )}

      {!isPending && !isError && services.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Title</th>
                <th className="py-2 pr-4 font-medium">Provider</th>
                <th className="py-2 pr-4 font-medium">Category</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {services.map((s) => (
                <tr key={s.id}>
                  <td className="py-3 pr-4 text-ink">{s.title}</td>
                  <td className="py-3 pr-4 text-ink-muted">{s.provider?.business_name ?? '—'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{s.category?.name ?? '—'}</td>
                  <td className="py-3 pr-4">
                    <Badge tone={s.is_active ? 'success' : 'neutral'}>{s.is_active ? 'Active' : 'Inactive'}</Badge>
                  </td>
                  <td className="py-3">
                    <Button
                      size="sm"
                      variant={s.is_active ? 'subtle' : 'outline'}
                      onClick={() => toggleMutation.mutate(s.id)}
                      loading={toggleMutation.isPending && toggleMutation.variables === s.id}
                    >
                      {s.is_active ? 'Deactivate' : 'Activate'}
                    </Button>
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
