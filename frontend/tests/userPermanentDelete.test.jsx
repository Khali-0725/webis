import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import UserListPage from '@/pages/admin/users/UserListPage';
import { userApi } from '@/services/api/admin/userApi';
import { publicApi } from '@/services/api/publicApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/services/api/admin/userApi', () => ({
  userApi: { list: vi.fn(), restore: vi.fn(), permanentRemove: vi.fn() },
}));
vi.mock('@/services/api/publicApi', () => ({
  publicApi: { listBarangays: vi.fn() },
}));

describe('admin user trash', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
    setSession(makeUser({ role: 'admin' }));
    publicApi.listBarangays.mockResolvedValue([]);
    userApi.list.mockImplementation(async (params) => ({
      items: params.trashed === 'only' ? [{
        id: 2,
        full_name: 'Deleted Provider',
        email: 'deleted@example.com',
        role_label: 'Service Provider',
        status_label: 'Active',
        deleted_at: '2026-10-22T00:00:00+08:00',
      }] : [],
      meta: { current_page: 1, last_page: 1 },
    }));
  });

  it('requires typing DELETE before permanently deleting a trashed account', async () => {
    const user = userEvent.setup();
    userApi.permanentRemove.mockResolvedValue(null);
    renderWithProviders(<UserListPage />);

    await user.click(screen.getByRole('button', { name: 'Trash' }));
    await screen.findByText('Deleted Provider');
    await user.click(screen.getByRole('button', { name: 'Delete permanently' }));
    expect(userApi.permanentRemove).not.toHaveBeenCalled();
    expect(screen.getByRole('button', { name: 'Confirm permanent deletion' })).toBeDisabled();
    await user.type(screen.getByLabelText('Type DELETE to confirm'), 'delete');
    expect(screen.getByRole('button', { name: 'Confirm permanent deletion' })).toBeDisabled();
    await user.clear(screen.getByLabelText('Type DELETE to confirm'));
    await user.type(screen.getByLabelText('Type DELETE to confirm'), 'DELETE');
    await user.click(screen.getByRole('button', { name: 'Confirm permanent deletion' }));
    await waitFor(() => expect(userApi.permanentRemove).toHaveBeenCalledExactlyOnceWith(2, 'DELETE'));
  });

  it('keeps the confirmation open when deletion fails', async () => {
    const user = userEvent.setup();
    userApi.permanentRemove.mockRejectedValue({
      message: 'Unable to delete this account.',
    });
    renderWithProviders(<UserListPage />);

    await user.click(screen.getByRole('button', { name: 'Trash' }));
    await screen.findByText('Deleted Provider');
    await user.click(screen.getByRole('button', { name: 'Delete permanently' }));
    await user.type(screen.getByLabelText('Type DELETE to confirm'), 'DELETE');
    await user.click(screen.getByRole('button', { name: 'Confirm permanent deletion' }));

    expect(await screen.findByText(/Unable to delete this account/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Restore' })).toBeInTheDocument();
  });
});
