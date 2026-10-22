import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SettingsPage from '@/pages/admin/settings/SettingsPage';
import { settingsApi } from '@/services/api/admin/settingsApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/admin/settingsApi', () => ({
  settingsApi: { list: vi.fn(), update: vi.fn() },
}));

describe('admin platform settings', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    settingsApi.list.mockResolvedValue([
      { key: 'platform.announcement', value: 'Previous notice' },
    ]);
  });

  it('fills missing settings with operational defaults before saving', async () => {
    settingsApi.update.mockResolvedValue([]);
    renderWithProviders(<SettingsPage />);

    expect(await screen.findByLabelText('Platform name')).toHaveValue('WEBIS');
    expect(screen.getByLabelText('Minimum booking lead time (hours)')).toHaveValue('24');
    expect(screen.getByLabelText('Maximum booking advance (days)')).toHaveValue('30');
    expect(screen.getByLabelText('Public announcement (leave blank to hide)')).toHaveValue('Previous notice');

    await userEvent.setup().click(screen.getByRole('button', { name: 'Save settings' }));

    await waitFor(() => expect(settingsApi.update).toHaveBeenCalledWith([
      { key: 'platform.name', value: 'WEBIS' },
      { key: 'booking.min_lead_hours', value: '24' },
      { key: 'booking.max_advance_days', value: '30' },
      { key: 'platform.announcement', value: 'Previous notice' },
    ]));
  });

  it('shows the rejected setting beside its input', async () => {
    settingsApi.update.mockRejectedValue({
      message: 'The submitted data is invalid.',
      errors: { 'settings.3.value': ['The announcement may not be greater than 500 characters.'] },
    });
    renderWithProviders(<SettingsPage />);

    await screen.findByLabelText('Platform name');
    await userEvent.setup().click(screen.getByRole('button', { name: 'Save settings' }));

    expect(await screen.findByText('The announcement may not be greater than 500 characters.')).toBeInTheDocument();
  });
});
