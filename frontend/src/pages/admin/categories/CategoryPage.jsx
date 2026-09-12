import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { categoryApi } from '@/services/api/categoryApi';
import { queryKeys } from '@/services/api/queryClient';

export default function CategoryPage() {
  const queryClient = useQueryClient();
  const [form, setForm] = useState({ name: '', description: '' });
  const [notice, setNotice] = useState(null);

  const { data: categories = [], isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.categories,
    queryFn: categoryApi.list,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.admin.categories });

  const createMutation = useMutation({
    mutationFn: categoryApi.create,
    onSuccess: () => {
      invalidate();
      setForm({ name: '', description: '' });
      setNotice({ tone: 'success', message: 'Category created successfully.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to create category.' }),
  });

  const toggleMutation = useMutation({
    mutationFn: categoryApi.toggle,
    onSuccess: invalidate,
  });

  const handleCreate = (event) => {
    event.preventDefault();
    setNotice(null);
    createMutation.mutate(form);
  };

  return (
    <div className="space-y-5">
      <Card title="Add Category">
        {notice && (
          <Alert tone={notice.tone} className="mb-4">
            {notice.message}
          </Alert>
        )}
        <form onSubmit={handleCreate} className="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
          <Input
            label="Name"
            required
            value={form.name}
            onChange={(event) => setForm({ ...form, name: event.target.value })}
            placeholder="e.g. Plumbing"
          />
          <Input
            label="Description"
            value={form.description}
            onChange={(event) => setForm({ ...form, description: event.target.value })}
            placeholder="Optional"
          />
          <Button type="submit" loading={createMutation.isPending}>
            Add category
          </Button>
        </form>
      </Card>

      <Card title="Service Categories">
        {isPending && <LoadingState label="Loading categories…" />}
        {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

        {!isPending && !isError && categories.length === 0 && (
          <EmptyState title="No categories yet" description="Add your first category above." />
        )}

        {!isPending && !isError && categories.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                  <th className="py-2 pr-4 font-medium">Name</th>
                  <th className="py-2 pr-4 font-medium">Description</th>
                  <th className="py-2 pr-4 font-medium">Status</th>
                  <th className="py-2 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {categories.map((category) => (
                  <tr key={category.id}>
                    <td className="py-3 pr-4 text-ink">{category.name}</td>
                    <td className="py-3 pr-4 text-ink-muted">{category.description || '—'}</td>
                    <td className="py-3 pr-4">
                      <Badge tone={category.is_active ? 'success' : 'neutral'}>
                        {category.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </td>
                    <td className="py-3">
                      <Button
                        size="sm"
                        variant={category.is_active ? 'subtle' : 'outline'}
                        onClick={() => toggleMutation.mutate(category.id)}
                        loading={toggleMutation.isPending && toggleMutation.variables === category.id}
                      >
                        {category.is_active ? 'Deactivate' : 'Activate'}
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
