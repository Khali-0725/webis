import { beforeEach, expect, it, vi } from 'vitest';
import { screen, within } from '@testing-library/react';
import SearchResultsPage from '@/pages/public/SearchResultsPage';
import { publicApi } from '@/services/api/publicApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/publicApi', () => ({ publicApi: {
  listCategories: vi.fn(), listBarangays: vi.fn(), searchServices: vi.fn(),
} }));

beforeEach(() => {
  vi.clearAllMocks();
  publicApi.listCategories.mockResolvedValue([]);
  publicApi.listBarangays.mockResolvedValue([]);
  publicApi.searchServices.mockResolvedValue({
    items: [{
      id: 7,
      title: 'Emergency Pipe Repair',
      description: 'Fast plumbing help',
      price: 800,
      pricing_type: 'fixed',
      category: { name: 'Plumbing' },
      provider: { business_name: 'Demo Provider Services', is_verified: true, base_barangay: { name: 'Amaya' }, rating_count: 0 },
    }],
    meta: { total: 1, current_page: 1, last_page: 1 },
  });
});

it('shows services as a labeled table and keeps the client detail route', async () => {
  renderWithProviders(<SearchResultsPage />, { route: '/client/search' });
  const table = await screen.findByRole('table', { name: 'Browse services' });
  const row = within(table).getByRole('row', { name: /Emergency Pipe Repair/ });

  expect(within(table).getByRole('columnheader', { name: 'Service' })).toBeInTheDocument();
  expect(within(table).getByRole('columnheader', { name: 'Provider' })).toBeInTheDocument();
  expect(within(table).getByRole('columnheader', { name: 'Price' })).toBeInTheDocument();
  expect(within(row).getByRole('link', { name: 'Emergency Pipe Repair' })).toHaveAttribute('href', '/client/services/7');
  expect(within(row).getByText('Amaya').closest('td')).toHaveAttribute('data-label', 'Barangay');
});

it('keeps guest service links on the public route', async () => {
  renderWithProviders(<SearchResultsPage />, { route: '/search' });
  const link = await screen.findByRole('link', { name: 'Emergency Pipe Repair' });
  expect(link).toHaveAttribute('href', '/services/7');
});
