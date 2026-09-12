import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { adminBarangayApi } from '@/services/api/admin/barangayApi';
import { queryKeys } from '@/services/api/queryClient';

export default function BarangayPage() {
  const queryClient = useQueryClient();
  const [form, setForm] = useState({ name: '', municipality: '', province: '' });
  const [notice, setNotice] = useState(null);

  const { data: barangays = [], isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.barangays,
    queryFn: adminBarangayApi.list,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.admin.barangays });

  const createMutation = useMutation({
    mutationFn: adminBarangayApi.create,
    onSuccess: () => {
      invalidate();
      setForm({ name: '', municipality: '', province: '' });
      setNotice({ tone: 'success', message: 'Barangay added.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to add barangay.' }),
  });

  const toggleMutation = useMutation({
    mutationFn: adminBarangayApi.toggle,
    onSuccess: invalidate,
  });

  const handleCreate = (event) => {
    event.preventDefault();
    setNotice(null);
    createMutation.mutate(form);
  };

  return (
    <div className="space-y-5">
      <Card title="Add Barangay">
        {notice && (
          <Alert tone={notice.tone} className="mb-4">
            {notice.message}
          </Alert>
        )}
        <form onSubmit={handleCreate} className="grid gap-4 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
          <Input
            label="Name"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
          />
          <Input
            label="Municipality"
            required
            value={form.municipality}
            onChange={(e) => setForm({ ...form, municipality: e.target.value })}
          />
          <Input
            label="Province"
            required
            value={form.province}
            onChange={(e) => setForm({ ...form, province: e.target.value })}
          />
          <Button type="submit" loading={createMutation.isPending}>
            Add
          </Button>
        </form>
      </Card>

      <Card title="Barangays">
        {isPending && <LoadingState label="Loading barangays…" />}
        {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

        {!isPending && !isError && barangays.length === 0 && (
          <EmptyState title="No barangays yet" description="Add your first barangay above." />
        )}

        {!isPending && !isError && barangays.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                  <th className="py-2 pr-4 font-medium">Name</th>
                  <th className="py-2 pr-4 font-medium">Municipality</th>
                  <th className="py-2 pr-4 font-medium">Province</th>
                  <th className="py-2 pr-4 font-medium">Status</th>
                  <th className="py-2 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {barangays.map((b) => (
                  <tr key={b.id}>
                    <td className="py-3 pr-4 text-ink">{b.name}</td>
                    <td className="py-3 pr-4 text-ink-muted">{b.municipality}</td>
                    <td className="py-3 pr-4 text-ink-muted">{b.province}</td>
                    <td className="py-3 pr-4">
                      <Badge tone={b.is_active ? 'success' : 'neutral'}>
                        {b.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </td>
                    <td className="py-3">
                      <Button
                        size="sm"
                        variant={b.is_active ? 'subtle' : 'outline'}
                        onClick={() => toggleMutation.mutate(b.id)}
                        loading={toggleMutation.isPending && toggleMutation.variables === b.id}
                      >
                        {b.is_active ? 'Deactivate' : 'Activate'}
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}
