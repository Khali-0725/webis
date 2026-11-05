import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CmsPage from '@/pages/admin/cms/CmsPage';
import { faqApi } from '@/services/api/admin/faqApi';
import { settingsApi } from '@/services/api/admin/settingsApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/services/api/admin/faqApi', () => ({
  faqApi: { list: vi.fn(), create: vi.fn(), update: vi.fn(), remove: vi.fn() },
}));
vi.mock('@/services/api/admin/settingsApi', () => ({
  settingsApi: { list: vi.fn(), update: vi.fn() },
}));

describe('Super Admin content management', () => {
  beforeEach(() => {
    resetSession();
    setSession(makeUser({ role: 'super_admin', home_path: '/admin/dashboard' }));
    vi.clearAllMocks();
    settingsApi.list.mockResolvedValue([{ key: 'platform.announcement', value: 'Current notice' }]);
    faqApi.list.mockResolvedValue([]);
  });

  it('saves only the announcement setting', async () => {
    settingsApi.update.mockResolvedValue([]);
    renderWithProviders(<CmsPage />);

    const announcement = await screen.findByLabelText('Announcement');
    expect(announcement).toHaveValue('Current notice');
    await userEvent.setup().clear(announcement);
    await userEvent.setup().type(announcement, 'Scheduled maintenance tonight');
    await userEvent.setup().click(screen.getByRole('button', { name: 'Save announcement' }));

    await waitFor(() => expect(settingsApi.update).toHaveBeenCalledWith([
      { key: 'platform.announcement', value: 'Scheduled maintenance tonight' },
    ]));
  });

  it('creates a draft FAQ with its display order', async () => {
    faqApi.create.mockResolvedValue({ id: 1 });
    renderWithProviders(<CmsPage />);

    await screen.findByText('No FAQs yet');
    const user = userEvent.setup();
    await user.type(screen.getByLabelText(/^Question/), 'How do I book?');
    await user.type(screen.getByLabelText('Answer'), 'Choose a service and an available time.');
    await user.clear(screen.getByLabelText(/^Display order/));
    await user.type(screen.getByLabelText(/^Display order/), '3');
    await user.click(screen.getByLabelText('Published'));
    await user.click(screen.getByRole('button', { name: 'Add FAQ' }));

    await waitFor(() => expect(faqApi.create).toHaveBeenCalledWith({
      question: 'How do I book?',
      answer: 'Choose a service and an available time.',
      sort_order: 3,
      is_published: false,
    }));
  });
});
