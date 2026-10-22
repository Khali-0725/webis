import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ProfilePage from '@/pages/settings/ProfilePage';
import { profileApi } from '@/services/api/profileApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/services/api/profileApi', () => ({
  profileApi: { updateProfile: vi.fn(), updateAvatar: vi.fn(), deleteAccount: vi.fn() },
}));

describe('profile avatar upload', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
    setSession(makeUser());
  });

  it('shows the avatar validation error returned by the server', async () => {
    profileApi.updateAvatar.mockRejectedValue({
      status: 422,
      message: 'The submitted data is invalid.',
      errors: { avatar: ['The avatar must be a valid PNG image.'] },
    });
    renderWithProviders(<ProfilePage />);

    await userEvent.setup().upload(
      screen.getByLabelText('Update avatar'),
      new File(['invalid'], 'avatar.png', { type: 'image/png' }),
    );

    expect(await screen.findByText('The avatar must be a valid PNG image.')).toBeInTheDocument();
  });

  it('rejects files over 4 MB before uploading', async () => {
    renderWithProviders(<ProfilePage />);
    const file = new File([new Uint8Array(4 * 1024 * 1024 + 1)], 'large.png', { type: 'image/png' });

    await userEvent.setup().upload(screen.getByLabelText('Update avatar'), file);

    expect(await screen.findByText('Choose an image up to 4 MB.')).toBeInTheDocument();
    await waitFor(() => expect(profileApi.updateAvatar).not.toHaveBeenCalled());
  });
});
