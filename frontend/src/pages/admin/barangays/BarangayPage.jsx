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
import { adminBarangayApi } from '@/services/api/admin/barangayApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';

const EMPTY = { name: '', municipality: '', province: '' };

export default function BarangayPage() {
  const queryClient = useQueryClient();
  const [form, setForm] = useState(EMPTY);
  const [trashed, setTrashed] = useState('');
  const [notice, setNotice] = useState(null);
  const [editing, setEditing] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);

  const params = trashed ? { trashed } : {};

  const { data: barangays = [], isPending, isError, error, refetch } = useQuery({
    queryKey: [...queryKeys.admin.barangays, params],
    queryFn: () => adminBarangayApi.list(params),
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: queryKeys.admin.barangays });
    queryClient.invalidateQueries({ queryKey: queryKeys.public.barangays });
  };

  const createMutation = useMutation({
    mutationFn: adminBarangayApi.create,
    onSuccess: () => {
      invalidate();
      setForm(EMPTY);
      setNotice({ tone: 'success', message: 'Barangay added.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to add barangay.' }),
  });

  const toggleMutation = useMutation({
    mutationFn: adminBarangayApi.toggle,
    onSuccess: invalidate,
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to update barangay.' }),
  });

  const restoreMutation = useMutation({
    mutationFn: adminBarangayApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Barangay restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore barangay.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: adminBarangayApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Barangay moved to trash.' });
    },
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
          <Input label="Name" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} error={fieldError(createMutation.error, 'name')} />
          <Input
            label="Municipality"
            required
            value={form.municipality}
            onChange={(e) => setForm({ ...form, municipality: e.target.value })}
            error={fieldError(createMutation.error, 'municipality')}
          />
          <Input label="Province" required value={form.province} onChange={(e) => setForm({ ...form, province: e.target.value })} error={fieldError(createMutation.error, 'province')} />
          <Button type="submit" loading={createMutation.isPending}>
            Add
          </Button>
        </form>
      </Card>

      <Card title="Barangays" action={<TrashToggle value={trashed} onChange={setTrashed} />}>
        {isPending && <LoadingState label="Loading barangays…" />}
        {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

        {!isPending && !isError && barangays.length === 0 && (
          <EmptyState
            title={trashed ? 'Trash is empty' : 'No barangays yet'}
            description={trashed ? 'Deleted barangays will appear here until restored.' : 'Add your first barangay above.'}
          />
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
                      {b.deleted_at ? (
                        <DeletedBadge deletedAt={b.deleted_at} />
                      ) : (
                        <Badge tone={b.is_active ? 'success' : 'neutral'}>{b.is_active ? 'Active' : 'Inactive'}</Badge>
                      )}
                    </td>
                    <td className="py-3">
                      <RowActions
                        record={b}
                        busy={
                          (toggleMutation.isPending && toggleMutation.variables === b.id) ||
                          (restoreMutation.isPending && restoreMutation.variables === b.id)
                        }
                        onEdit={setEditing}
                        onToggle={(row) => toggleMutation.mutate(row.id)}
                        onDelete={setPendingDelete}
                        onRestore={(row) => restoreMutation.mutate(row.id)}
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
        <BarangayEditor
          barangay={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            invalidate();
            setNotice({ tone: 'success', message: 'Barangay updated.' });
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
        title="Delete this barangay?"
        description={pendingDelete ? `${pendingDelete.name}, ${pendingDelete.municipality}` : undefined}
        confirmLabel="Delete barangay"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          It is moved to the trash and hidden from every picker. A barangay that users, providers or
          bookings still point at cannot be deleted - deactivate it instead.
        </p>
      </ConfirmDialog>
    </div>
  );
}

function BarangayEditor({ barangay, onClose, onSaved }) {
  const [form, setForm] = useState({
    name: barangay.name ?? '',
    municipality: barangay.municipality ?? '',
    province: barangay.province ?? '',
  });

  const mutation = useMutation({
    mutationFn: (payload) => adminBarangayApi.update(barangay.id, payload),
    onSuccess: onSaved,
  });

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit barangay"
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="barangay-editor-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="barangay-editor-form"
        className="grid gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          mutation.mutate(form);
        }}
      >
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <Input label="Name" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} error={fieldError(err, 'name')} />
        <Input
          label="Municipality"
          required
          value={form.municipality}
          onChange={(e) => setForm({ ...form, municipality: e.target.value })}
          error={fieldError(err, 'municipality')}
        />
        <Input label="Province" required value={form.province} onChange={(e) => setForm({ ...form, province: e.target.value })} error={fieldError(err, 'province')} />
      </form>
    </Modal>
  );
}
