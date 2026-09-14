import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select, Textarea } from '@/components/ui/Select';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { RowActions } from '@/components/ui/RowActions';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { adminServiceApi } from '@/services/api/admin/serviceApi';
import { publicApi } from '@/services/api/publicApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';

export default function ServiceListPage() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [trashed, setTrashed] = useState('');
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [editing, setEditing] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const params = { ...(search ? { search } : {}), ...(trashed ? { trashed } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.services(params),
    queryFn: () => adminServiceApi.list(params),
  });

  const { data: categories = [] } = useQuery({
    queryKey: queryKeys.public.categories,
    queryFn: publicApi.listCategories,
  });

  const services = data?.items ?? [];
  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'services'] });

  const toggleMutation = useMutation({
    mutationFn: adminServiceApi.toggle,
    onSuccess: invalidate,
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to update service.' }),
  });

  const restoreMutation = useMutation({
    mutationFn: adminServiceApi.restore,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Service restored.' });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Failed to restore service.' }),
  });

  const deleteMutation = useMutation({
    mutationFn: adminServiceApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Service moved to trash.' });
    },
  });

  return (
    <Card title="Services" action={<TrashToggle value={trashed} onChange={(v) => { setTrashed(v); setPage(1); }} />}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

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
        <EmptyState
          title={trashed ? 'Trash is empty' : 'No services found'}
          description={trashed ? 'Deleted services will appear here until restored.' : 'Try a different search term.'}
        />
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
                    {s.deleted_at ? (
                      <DeletedBadge deletedAt={s.deleted_at} />
                    ) : (
                      <Badge tone={s.is_active ? 'success' : 'neutral'}>{s.is_active ? 'Active' : 'Inactive'}</Badge>
                    )}
                  </td>
                  <td className="py-3">
                    <RowActions
                      record={s}
                      busy={
                        (toggleMutation.isPending && toggleMutation.variables === s.id) ||
                        (restoreMutation.isPending && restoreMutation.variables === s.id)
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

      <Pagination meta={data?.meta} onChange={setPage} />

      {editing && (
        <ServiceEditor
          service={editing}
          categories={categories}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            invalidate();
            setNotice({ tone: 'success', message: 'Service updated.' });
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
        title="Delete this service?"
        description={pendingDelete?.title}
        confirmLabel="Delete service"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          The listing leaves search and the provider&apos;s list. Bookings already made against it are kept
          and still show the service. You can restore it from the Trash view.
        </p>
      </ConfirmDialog>
    </Card>
  );
}

function ServiceEditor({ service, categories, onClose, onSaved }) {
  const [form, setForm] = useState({
    title: service.title ?? '',
    description: service.description ?? '',
    service_category_id: service.category?.id ?? '',
    price: service.price ?? '',
  });

  const mutation = useMutation({
    mutationFn: (payload) => adminServiceApi.update(service.id, payload),
    onSuccess: onSaved,
  });

  const err = mutation.error;
  const set = (field) => (e) => setForm({ ...form, [field]: e.target.value });

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit service"
      description={service.provider?.business_name ? `Listed by ${service.provider.business_name}` : undefined}
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="service-editor-form" loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <form
        id="service-editor-form"
        className="grid gap-4"
        onSubmit={(event) => {
          event.preventDefault();
          const payload = { title: form.title, description: form.description };
          if (form.service_category_id) payload.service_category_id = Number(form.service_category_id);
          if (form.price !== '') payload.price = Number(form.price);
          mutation.mutate(payload);
        }}
      >
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <Input label="Title" required value={form.title} onChange={set('title')} error={fieldError(err, 'title')} />
        <Textarea label="Description" required rows={4} value={form.description} onChange={set('description')} error={fieldError(err, 'description')} />
        <div className="grid gap-4 sm:grid-cols-2">
          <Select
            label="Category"
            value={form.service_category_id}
            onChange={set('service_category_id')}
            error={fieldError(err, 'service_category_id')}
            options={[{ value: '', label: 'Keep current' }, ...categories.map((c) => ({ value: String(c.id), label: c.name }))]}
          />
          <Input label="Price (₱)" type="number" min="0" step="0.01" value={form.price} onChange={set('price')} error={fieldError(err, 'price')} />
        </div>
      </form>
    </Modal>
  );
}
