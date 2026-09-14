import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Pagination } from '@/components/ui/Pagination';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { userApi } from '@/services/api/admin/userApi';
import { publicApi } from '@/services/api/publicApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';
import { ROLE_LABELS } from '@/constants';
import { useAuth } from '@/hooks/useAuth';

const ROLE_OPTIONS = ['', 'client', 'provider', 'admin'];
const STATUS_OPTIONS = ['', 'active', 'suspended'];

const EMPTY_FORM = {
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: 'client',
  barangay_id: '',
  password: '',
};

function formFromUser(user) {
  return {
    first_name: user.first_name ?? '',
    last_name: user.last_name ?? '',
    email: user.email ?? '',
    phone: user.phone ?? '',
    role: user.role,
    barangay_id: user.barangay_id ?? '',
    password: '',
  };
}

export default function UserListPage() {
  const queryClient = useQueryClient();
  const { user: me } = useAuth();
  const [filters, setFilters] = useState({ role: '', status: '', search: '', trashed: '' });
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [editor, setEditor] = useState(null); // { mode: 'create' } | { mode: 'edit', user }
  const [pendingDelete, setPendingDelete] = useState(null);

  const params = { ...Object.fromEntries(Object.entries(filters).filter(([, v]) => v)), page };
  const inTrash = filters.trashed === 'only';

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.users(params),
    queryFn: () => userApi.list(params),
  });

  const { data: barangays = [] } = useQuery({
    queryKey: queryKeys.public.barangays,
    queryFn: publicApi.listBarangays,
  });

  const users = data?.items ?? [];

  const updateFilter = (patch) => {
    setFilters({ ...filters, ...patch });
    setPage(1);
  };

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin', 'users'] });

  const act = async (fn, successMessage, fallback) => {
    setNotice(null);
    try {
      await fn();
      await invalidate();
      setNotice({ tone: 'success', message: successMessage });
    } catch (err) {
      setNotice({ tone: 'error', message: err?.message ?? fallback });
    }
  };

  const deleteMutation = useMutation({
    mutationFn: (id) => userApi.remove(id),
    onSuccess: async () => {
      await invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'User moved to trash. You can restore it from the Trash view.' });
    },
  });

  return (
    <Card
      title="Users"
      action={
        <Button size="sm" onClick={() => setEditor({ mode: 'create' })}>
          Add user
        </Button>
      }
    >
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <div className="mb-4 grid gap-3 sm:grid-cols-[auto_1fr_1fr_1fr]">
        <TrashToggle value={filters.trashed} onChange={(trashed) => updateFilter({ trashed })} />
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
        <EmptyState
          title={inTrash ? 'Trash is empty' : 'No users found'}
          description={inTrash ? 'Deleted users will appear here until restored.' : 'Try adjusting the filters above.'}
        />
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
                <tr key={u.id} className={u.deleted_at ? 'text-ink-muted' : undefined}>
                  <td className="py-3 pr-4 text-ink">
                    {u.full_name}
                    {u.id === me?.id && <span className="ml-2 text-xs text-ink-muted">(you)</span>}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{u.email}</td>
                  <td className="py-3 pr-4 text-ink-muted">{u.role_label}</td>
                  <td className="py-3 pr-4">
                    {u.deleted_at ? (
                      <DeletedBadge deletedAt={u.deleted_at} />
                    ) : (
                      <Badge tone={u.status === 'active' ? 'success' : 'danger'}>{u.status_label}</Badge>
                    )}
                  </td>
                  <td className="py-3">
                    {u.deleted_at ? (
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => act(() => userApi.restore(u.id), 'User restored.', 'Failed to restore user.')}
                      >
                        Restore
                      </Button>
                    ) : (
                      <div className="flex flex-wrap gap-2">
                        <Button size="sm" variant="subtle" onClick={() => setEditor({ mode: 'edit', user: u })}>
                          Edit
                        </Button>
                        {u.status === 'active' ? (
                          <Button
                            size="sm"
                            variant="subtle"
                            onClick={() => act(() => userApi.suspend(u.id), 'User suspended.', 'Failed to suspend user.')}
                          >
                            Suspend
                          </Button>
                        ) : (
                          <Button
                            size="sm"
                            variant="success"
                            onClick={() => act(() => userApi.activate(u.id), 'User activated.', 'Failed to activate user.')}
                          >
                            Activate
                          </Button>
                        )}
                        <Button
                          size="sm"
                          variant="danger"
                          disabled={u.id === me?.id}
                          title={u.id === me?.id ? 'You cannot delete your own account here' : undefined}
                          onClick={() => setPendingDelete(u)}
                        >
                          Delete
                        </Button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />

      {editor && (
        <UserEditor
          mode={editor.mode}
          user={editor.user}
          barangays={barangays}
          isSelf={editor.user?.id === me?.id}
          onClose={() => setEditor(null)}
          onSaved={async (message) => {
            setEditor(null);
            await invalidate();
            setNotice({ tone: 'success', message });
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
        title="Delete this user?"
        description={pendingDelete ? `${pendingDelete.full_name} (${pendingDelete.email})` : undefined}
        confirmLabel="Delete user"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          The account is moved to the trash: it can no longer sign in and disappears from lists, but its
          bookings, payments and messages stay on record. You can restore it later.
        </p>
      </ConfirmDialog>
    </Card>
  );
}

function UserEditor({ mode, user, barangays, isSelf, onClose, onSaved }) {
  const isEdit = mode === 'edit';
  const [form, setForm] = useState(isEdit ? formFromUser(user) : EMPTY_FORM);

  const mutation = useMutation({
    mutationFn: (payload) => (isEdit ? userApi.update(user.id, payload) : userApi.create(payload)),
    onSuccess: () => onSaved(isEdit ? 'User updated.' : 'User created.'),
  });

  const set = (field) => (event) => setForm({ ...form, [field]: event.target.value });

  const submit = (event) => {
    event.preventDefault();
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone || null,
      barangay_id: form.barangay_id || null,
      role: form.role,
    };
    if (form.password || !isEdit) payload.password = form.password;
    mutation.mutate(payload);
  };

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title={isEdit ? 'Edit user' : 'Add user'}
      description={isEdit ? user.email : 'The account is active immediately; no verification email is sent.'}
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button type="submit" form="user-editor-form" loading={mutation.isPending}>
            {isEdit ? 'Save changes' : 'Create user'}
          </Button>
        </>
      }
    >
      <form id="user-editor-form" onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
        {err && !Object.keys(err.errors ?? {}).length && (
          <Alert tone="error" className="sm:col-span-2">
            {err.message}
          </Alert>
        )}
        <Input label="First name" required value={form.first_name} onChange={set('first_name')} error={fieldError(err, 'first_name')} />
        <Input label="Last name" required value={form.last_name} onChange={set('last_name')} error={fieldError(err, 'last_name')} />
        <Input label="Email" type="email" required value={form.email} onChange={set('email')} error={fieldError(err, 'email')} className="sm:col-span-2" />
        <Input label="Mobile number" value={form.phone} onChange={set('phone')} placeholder="09171234567" error={fieldError(err, 'phone')} />
        <Select
          label="Role"
          value={form.role}
          onChange={set('role')}
          disabled={isSelf}
          hint={isSelf ? 'You cannot change your own role.' : undefined}
          error={fieldError(err, 'role')}
          options={Object.entries(ROLE_LABELS).map(([value, label]) => ({ value, label }))}
        />
        <Select
          label="Barangay"
          value={form.barangay_id}
          onChange={set('barangay_id')}
          error={fieldError(err, 'barangay_id')}
          options={[{ value: '', label: 'Not set' }, ...barangays.map((b) => ({ value: String(b.id), label: b.name }))]}
        />
        <Input
          label={isEdit ? 'New password' : 'Password'}
          type="password"
          required={!isEdit}
          value={form.password}
          onChange={set('password')}
          hint={isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters with letters and numbers.'}
          error={fieldError(err, 'password')}
          autoComplete="new-password"
        />
      </form>
    </Modal>
  );
}
