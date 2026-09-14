import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { categoryApi } from '@/services/api/categoryApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';

export default function CategoryPage() {
  const queryClient = useQueryClient();
  const [form, setForm] = useState({ name: '', description: '' });
  const [trashed, setTrashed] = useState('');
  const [notice, setNotice] = useState(null);
  const [editing, setEditing] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);

  const params = trashed ? { trashed } : {};

  const { data: categories = [], isPending, isError, error, refetch } = useQuery({
    queryKey: [...queryKeys.admin.categories, params],
    queryFn: () => categoryApi.list(params),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.admin.categories });

  const createMutation = useMutation({
    mutationFn: categoryApi.create,
    onSuccess: () => {
      invalidate();
      setForm({ name: '', description: '' });
      setNotice({ tone: 'success', message: 'Category created.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to create category.' }),
  });

  const toggleMutation = useMutation({
    mutationFn: categoryApi.toggle,
    onSuccess: invalidate,
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to update category.' }),
  });

  const restoreMutation = useMutation({
    mutationFn: categoryApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Category restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore category.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: categoryApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Category moved to trash.' });
    },
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
            error={fieldError(createMutation.error, 'name')}
          />
          <Input
            label="Description"
            value={form.description}
            onChange={(event) => setForm({ ...form, description: event.target.value })}
            placeholder="Optional"
            error={fieldError(createMutation.error, 'description')}
          />
          <Button type="submit" loading={createMutation.isPending}>
            Add category
          </Button>
        </form>
      </Card>

      <Card title="Service Categories" action={<TrashToggle value={trashed} onChange={setTrashed} />}>
        {isPending && <LoadingState label="Loading categories…" />}
        {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

        {!isPending && !isError && categories.length === 0 && (
          <EmptyState
            title={trashed ? 'Trash is empty' : 'No categories yet'}
            description={trashed ? 'Deleted categories will appear here until restored.' : 'Add your first category above.'}
          />
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
                      {category.deleted_at ? (
                        <DeletedBadge deletedAt={category.deleted_at} />
                      ) : (
                        <Badge tone={category.is_active ? 'success' : 'neutral'}>
                          {category.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      )}
                    </td>
                    <td className="py-3">
                      <RowActions
                        record={category}
                        busy={
                          (toggleMutation.isPending && toggleMutation.variables === category.id) ||
                          (restoreMutation.isPending && restoreMutation.variables === category.id)
                        }
                        onEdit={setEditing}
                        onToggle={(c) => toggleMutation.mutate(c.id)}
                        onDelete={setPendingDelete}
                        onRestore={(c) => restoreMutation.mutate(c.id)}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      {editing && (
        <CategoryEditor
          category={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            invalidate();
            setNotice({ tone: 'success', message: 'Category updated.' });
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(pendingDelete)}
        onClose={() => {
          setPendingDelete(null);
          deleteMutation.reset();
        }}
        onConfirm={() => deleteMutation.mutate(pendingDelete.id)}
        title="Delete this category?"
        description={pendingDelete?.name}
        confirmLabel="Delete category"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          It is moved to the trash and hidden everywhere. A category that still has services under it
          cannot be deleted - deactivate it instead.
        </p>
      </ConfirmDialog>
    </div>
  );
}

function CategoryEditor({ category, onClose, onSaved }) {
  const [form, setForm] = useState({
    name: category.name ?? '',
    description: category.description ?? '',
    sort_order: category.sort_order ?? 0,
  });

  const mutation = useMutation({
    mutationFn: (payload) => categoryApi.update(category.id, payload),
    onSuccess: onSaved,
  });

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit category"
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="category-editor-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="category-editor-form"
        className="grid gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          mutation.mutate({
            name: form.name,
            description: form.description || null,
            sort_order: Number(form.sort_order) || 0,
          });
        }}
      >
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <Input label="Name" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} error={fieldError(err, 'name')} />
        <Input
          label="Description"
          value={form.description}
          onChange={(e) => setForm({ ...form, description: e.target.value })}
          error={fieldError(err, 'description')}
        />
        <Input
          label="Sort order"
          type="number"
          min="0"
          value={form.sort_order}
          onChange={(e) => setForm({ ...form, sort_order: e.target.value })}
          hint="Lower numbers appear first in the public list."
          error={fieldError(err, 'sort_order')}
        />
      </form>
    </Modal>
  );
}
