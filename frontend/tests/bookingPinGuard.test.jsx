import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Route, Routes } from 'react-router-dom';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ServiceDetailPage from '@/pages/public/ServiceDetailPage';
import { publicApi } from '@/services/api/publicApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/components/map/LocationPicker', () => ({
  LocationPicker: ({ onChange, latitude, suggestedCenter }) => (
    <div>
      <button type="button" onClick={() => onChange(39.9, 116.4)}>Pin China</button>
      <button type="button" onClick={() => onChange(14.326, 120.853)}>Pin Mulawin</button>
      <button type="button" onClick={() => onChange(14.352, 120.866)}>Pin Halayhay</button>
      <output>Selected pin: {latitude ?? 'none'}; center: {suggestedCenter?.latitude ?? 'none'}</output>
    </div>
  ),
}));
vi.mock('@/services/api/publicApi', () => ({
  publicApi: { getService: vi.fn(), listBarangays: vi.fn() },
}));
vi.mock('@/services/api/bookingApi', () => ({
  bookingApi: { create: vi.fn(), getAvailability: vi.fn() },
}));
vi.mock('@/services/api/conversationApi', () => ({
  conversationApi: { start: vi.fn() },
}));

describe('booking pin guard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    resetSession();
    setSession(makeUser());
    publicApi.getService.mockResolvedValue({
      id: 5,
      title: 'House cleaning',
      description: 'Cleaning service',
      price: 500,
      pricing_type: 'fixed',
      category: { name: 'Cleaning' },
      provider: { id: 9, business_name: 'Provider', is_verified: true },
    });
    publicApi.listBarangays.mockResolvedValue([
      { id: 1, name: 'Mulawin', province: 'Cavite', municipality: 'Tanza', latitude: 14.326, longitude: 120.853 },
      { id: 2, name: 'Halayhay', province: 'Cavite', municipality: 'Tanza', latitude: 14.352, longitude: 120.866 },
    ]);
  });

  it('rejects a distant click and clears a valid pin when barangay changes', async () => {
    const user = userEvent.setup();
    renderWithProviders(<Routes><Route path="/services/:id" element={<ServiceDetailPage />} /></Routes>, { route: '/services/5' });
    await user.click(await screen.findByRole('button', { name: 'Book this service' }));
    await user.selectOptions(screen.getByLabelText(/Province/), 'Cavite');
    await user.selectOptions(screen.getByLabelText(/Municipality/), 'Tanza');
    await user.selectOptions(screen.getByLabelText(/Barangay/), '1');

    await user.click(screen.getByRole('button', { name: 'Pin China' }));
    expect(screen.getByText(/within 2 km of Mulawin/)).toBeInTheDocument();
    expect(screen.getByText(/Selected pin: none/)).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Pin Mulawin' }));
    expect(screen.getByText(/Selected pin: 14.326/)).toBeInTheDocument();
    await user.selectOptions(screen.getByLabelText(/Barangay/), '2');
    expect(screen.getByText(/Selected pin: none; center: 14.352/)).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Pin Halayhay' }));
    expect(screen.getByText(/Selected pin: 14.352; center: 14.352/)).toBeInTheDocument();
  });
});
