import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Textarea } from '@/components/ui/Select';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { providerApi } from '@/services/api/admin/providerApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_OPTIONS = ['', 'pending', 'approved', 'rejected'];
const STATUS_TONE = { pending: 'warning', approved: 'success', rejected: 'danger' };

export default function ProviderListPage() {
  const queryClient = useQueryClient();
  const [filters, setFilters] = useState({ verification_status: '', search: '', trashed: '' });
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [editing, setEditing] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const params = { ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)), page };
  const inTrash = filters.trashed === 'only';

  const updateFilter = (patch) => {
    setFilters({ ...filters, ...patch });
    setPage(1);
  };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.providers(params),
    queryFn: () => providerApi.list(params),
  });

  const providers = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'providers'] });

  const restoreMutation = useMutation({
    mutationFn: providerApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Provider restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore provider.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: providerApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Provider profile moved to trash.' });
    },
  });

  return (
    <Card title="Providers" action={<TrashToggle value={filters.trashed} onChange={(trashed) => updateFilter({ trashed })} />}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

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
        <Input placeholder="Search business name" value={filters.search} onChange={(e) => updateFilter({ search: e.target.value })} />
      </div>

      {isPending && <LoadingState label="Loading providers…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && providers.length === 0 && (
        <EmptyState
          title={inTrash ? 'Trash is empty' : 'No providers found'}
          description={inTrash ? 'Deleted provider profiles will appear here until restored.' : 'Try adjusting the filters above.'}
        />
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
                <th className="py-2 pr-4 font-medium">Completed</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {providers.map((p) => (
                <tr key={p.id}>
                  <td className="py-3 pr-4 text-ink">
                    <Link to={`/admin/providers/${p.id}`} className="font-medium hover:underline">
                      {p.business_name || 'Unnamed provider'}
                    </Link>
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{p.owner?.full_name ?? '—'}</td>
                  <td className="py-3 pr-4">
                    {p.deleted_at ? (
                      <DeletedBadge deletedAt={p.deleted_at} />
                    ) : (
                      <Badge tone={STATUS_TONE[p.verification_status] ?? 'neutral'}>{p.verification_status_label}</Badge>
                    )}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{p.rating_avg ? `${p.rating_avg} (${p.rating_count})` : '—'}</td>
                  <td className="py-3 pr-4 text-ink-muted">{p.completed_bookings_count}</td>
                  <td className="py-3">
                    <RowActions
                      record={p}
                      busy={restoreMutation.isPending && restoreMutation.variables === p.id}
                      onEdit={setEditing}
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

      <Pagination meta={data?.meta} onChange={setPage} />

      {editing && (
        <ProviderEditor
          provider={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            invalidate();
            setNotice({ tone: 'success', message: 'Provider updated.' });
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
        title="Delete this provider profile?"
        description={pendingDelete?.business_name}
        confirmLabel="Delete provider"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          The profile leaves search and the directory; its bookings, payments and reviews are kept on
          record. The owner&apos;s login is not affected - suspend or delete the user separately if needed.
        </p>
      </ConfirmDialog>
    </Card>
  );
}

function ProviderEditor({ provider, onClose, onSaved }) {
  const [form, setForm] = useState({
    business_name: provider.business_name ?? '',
    bio: provider.bio ?? '',
  });

  const mutation = useMutation({
    mutationFn: (payload) => providerApi.update(provider.id, payload),
    onSuccess: onSaved,
  });

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit provider"
      description={provider.owner?.email}
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="provider-editor-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="provider-editor-form"
        className="grid gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          mutation.mutate({ business_name: form.business_name || null, bio: form.bio || null });
        }}
      >
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <Input
          label="Business name"
          value={form.business_name}
          onChange={(e) => setForm({ ...form, business_name: e.target.value })}
          error={fieldError(err, 'business_name')}
        />
        <Textarea label="Bio" rows={4} value={form.bio} onChange={(e) => setForm({ ...form, bio: e.target.value })} error={fieldError(err, 'bio')} />
      </form>
    </Modal>
  );
}
