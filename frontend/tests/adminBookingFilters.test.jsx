import { expect, it, vi } from 'vitest';
import { fireEvent, screen, within } from '@testing-library/react';
import AdminBookingListPage from '@/pages/admin/bookings/BookingListPage';
import { bookingApi } from '@/services/api/bookingApi';
import { publicApi } from '@/services/api/publicApi';
import { providerApi } from '@/services/api/admin/providerApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/bookingApi', () => ({ bookingApi: { list: vi.fn(), restore: vi.fn(), remove: vi.fn() } }));
vi.mock('@/services/api/publicApi', () => ({ publicApi: { listCategories: vi.fn() } }));
vi.mock('@/services/api/admin/providerApi', () => ({ providerApi: { list: vi.fn() } }));

it('keeps booking search and filters in one responsive toolbar', async () => {
  bookingApi.list.mockResolvedValue({ items: [], meta: { total: 0 } });
  publicApi.listCategories.mockResolvedValue([]);
  providerApi.list.mockResolvedValue({ items: [] });
  renderWithProviders(<AdminBookingListPage />);

  const toolbar = await screen.findByTestId('admin-filter-toolbar');
  expect(within(toolbar).getByRole('textbox', { name: 'Search providers' })).toBeInTheDocument();
  expect(within(toolbar).getByRole('combobox', { name: 'Filter by provider' })).toBeInTheDocument();
  expect(within(toolbar).getByRole('combobox', { name: 'Filter by category' })).toBeInTheDocument();
  expect(within(toolbar).getByRole('combobox', { name: 'Filter by status' })).toBeInTheDocument();
  expect(within(toolbar).getByRole('combobox', { name: 'Date range' })).toBeInTheDocument();
  expect(within(toolbar).getByRole('combobox', { name: 'Sort records' })).toBeInTheDocument();
  fireEvent.change(within(toolbar).getByRole('combobox', { name: 'Date range' }), { target: { value: 'custom' } });
  expect(within(toolbar).getByLabelText('Scheduled from')).toBeInTheDocument();
  expect(within(toolbar).getByLabelText('Scheduled through')).toBeInTheDocument();
});
