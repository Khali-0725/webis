import { useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { userApi } from '@/services/api/admin/userApi';
import { queryKeys } from '@/services/api/queryClient';

const ROLE_OPTIONS = ['', 'client', 'provider', 'admin'];
const STATUS_OPTIONS = ['', 'active', 'suspended'];

export default function UserListPage() {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState({ role: '', status: '', search: '' });
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);

  const params = { ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.users(params),
    queryFn: () => userApi.list(params),
  });

  const users = data?.items ?? [];

  const updateFilter = (patch) => {
    setFilters({ ...filters, ...patch });
    setPage(1);
  };

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'users'] });

  const handleAction = async (id, action) => {
    const verb = action === 'suspend' ? 'suspend' : 'activate';
    if (!confirm(`Are you sure you want to ${verb} this user?`)) return;

    setNotice(null);
    try {
      if (action === 'suspend') await userApi.suspend(id);
      else await userApi.activate(id);

      await invalidate();
      setNotice({ tone: 'success', message: `User ${verb}d successfully.` });
    } catch (err) {
      setNotice({ tone: 'error', message: err?.message ?? `Failed to ${verb} user.` });
    }
  };

  return (
    <Card title="Users">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <div className="mb-4 grid gap-3 sm:grid-cols-3">
        <select
          aria-label="Filter by role"
          className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
          value={filters.role}
          onChange={(e) => updateFilter({ role: e.target.value })}
        >
          {ROLE_OPTIONS.map((r) => (
            <option key={r} value={r}>
              {r ? r[0].toUpperCase() + r.slice(1) : 'All roles'}
            </option>
          ))}
        </select>
        <select
          aria-label="Filter by status"
          className="h-10 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
          value={filters.status}
          onChange={(e) => updateFilter({ status: e.target.value })}
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s ? s[0].toUpperCase() + s.slice(1) : 'All statuses'}
            </option>
          ))}
        </select>
        <Input
          placeholder="Search name or email"
          value={filters.search}
          onChange={(e) => updateFilter({ search: e.target.value })}
        />
      </div>

      {isPending && <LoadingState label="Loading users…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && users.length === 0 && (
        <EmptyState title="No users found" description="Try adjusting the filters above." />
      )}

      {!isPending && !isError && users.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Name</th>
                <th className="py-2 pr-4 font-medium">Email</th>
                <th className="py-2 pr-4 font-medium">Role</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {users.map((u) => (
                <tr key={u.id}>
                  <td className="py-3 pr-4 text-ink">{u.full_name}</td>
                  <td className="py-3 pr-4 text-ink-muted">{u.email}</td>
                  <td className="py-3 pr-4 text-ink-muted">{u.role_label}</td>
                  <td className="py-3 pr-4">
                    <Badge tone={u.status === 'active' ? 'success' : 'danger'}>
                      {u.status_label}
                    </Badge>
                  </td>
                  <td className="py-3">
                    {u.status === 'active' ? (
                      <Button size="sm" variant="danger" onClick={() => handleAction(u.id, 'suspend')}>
                        Suspend
                      </Button>
                    ) : (
                      <Button size="sm" variant="success" onClick={() => handleAction(u.id, 'activate')}>
                        Activate
                      </Button>
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
