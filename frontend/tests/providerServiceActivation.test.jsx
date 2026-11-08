import { describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ServiceListPage from '@/pages/provider/services/ServiceListPage';
import { providerApi } from '@/services/api/providerApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/providerApi', () => ({ providerApi: {
  listMyServices: vi.fn(), getMyProfile: vi.fn(), publishService: vi.fn(),
  deactivateService: vi.fn(), restoreService: vi.fn(), deleteService: vi.fn(),
} }));

describe('inactive service activation', () => {
  it('explains paused publication and reactivates through the accessible icon action', async () => {
    providerApi.getMyProfile.mockResolvedValue({ verification_status: 'approved' });
    providerApi.listMyServices.mockResolvedValue({ items: [{ id: 7, title: 'Pipe repair', is_published: true, is_active: false, pricing_type: 'fixed', price: 500 }], meta: null });
    providerApi.publishService.mockResolvedValue({ id: 7, is_active: true });
    const user = userEvent.setup();
    renderWithProviders(<ServiceListPage />);
    expect(await screen.findByText('Publication paused')).toBeInTheDocument();
    expect(screen.queryByText('Published')).not.toBeInTheDocument();
    const activate = screen.getByRole('button', { name: 'Activate Pipe repair' });
    expect(activate).toHaveAttribute('title', 'Activate Pipe repair');
    await user.click(activate);
    await waitFor(() => expect(providerApi.publishService).toHaveBeenCalledTimes(1));
    expect(providerApi.publishService.mock.calls[0][0]).toBe(7);
  });
});
