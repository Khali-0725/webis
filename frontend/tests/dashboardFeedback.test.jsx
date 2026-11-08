import { beforeEach, expect, it, vi } from 'vitest';
import { screen, within } from '@testing-library/react';
import { useQuery } from '@tanstack/react-query';
import AdminDashboard from '@/pages/admin/AdminDashboard';
import ProviderDashboard from '@/pages/provider/ProviderDashboard';
import ClientDashboard from '@/pages/client/ClientDashboard';
import ReviewListPage from '@/pages/provider/reviews/ReviewListPage';
import AnalyticsPage from '@/pages/admin/analytics/AnalyticsPage';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@tanstack/react-query', async (importOriginal) => ({ ...await importOriginal(), useQuery: vi.fn() }));
vi.mock('@/components/layout/ApiStatusCard', () => ({ ApiStatusCard: () => <p>System status</p> }));

beforeEach(() => {
  resetSession();
  setSession(makeUser({ first_name: 'Jamie' }));
  useQuery.mockReturnValue({ isPending: true });
});

it('keeps four priority admin shortcuts with consistent icons and system status', () => {
  renderWithProviders(<AdminDashboard />);
  const card = screen.getByRole('heading', { name: 'Quick links' }).closest('section');
  const links = within(card).getAllByRole('link');
  expect(links.map((link) => link.getAttribute('href'))).toEqual([
    '/admin/users', '/admin/verification', '/admin/bookings', '/admin/reports',
  ]);
  links.forEach((link) => expect(link.querySelector('svg')).toBeTruthy());
  expect(screen.getByText('System status')).toBeInTheDocument();
});

it.each([ProviderDashboard, ClientDashboard])('welcomes the user by name', (Dashboard) => {
  renderWithProviders(<Dashboard />);
  expect(screen.getByText('Welcome, Jamie!')).toBeInTheDocument();
});

it('keeps technical system status off the provider dashboard', () => {
  renderWithProviders(<ProviderDashboard />);
  expect(screen.queryByText('System status')).not.toBeInTheDocument();
});

it('shows verified status beside services without an empty verification card', () => {
  useQuery.mockReturnValue({ isPending: true, data: { verification_status: 'approved', items: [] } });
  renderWithProviders(<ProviderDashboard />);
  expect(within(screen.getByRole('heading', { name: /My Services/ })).getByRole('img', { name: 'Verified provider by WEBIS' })).toBeInTheDocument();
  expect(screen.queryByRole('heading', { name: 'Verification' })).not.toBeInTheDocument();
});

it('labels platform payments as volume rather than admin earnings', () => {
  useQuery.mockReturnValueOnce({ data: { users: {}, providers: {}, bookings: {}, payments: { total_earnings: 3000 } } });
  // The remaining queries can stay loading while the overview is displayed.
  useQuery.mockReturnValue({ isPending: true });
  renderWithProviders(<AnalyticsPage />);
  expect(screen.getByText('Verified Payment Volume')).toBeInTheDocument();
  expect(screen.getByRole('heading', { name: 'Verified Payment Volume (last 30 days)' })).toBeInTheDocument();
  expect(screen.queryByText(/Earnings/)).not.toBeInTheDocument();
});

it('identifies each provider review by its booking, service and date', () => {
  useQuery.mockReturnValue({ data: { items: [{ id: 1, booking_id: 12, booking_reference: 'WEB-0012', scheduled_date: '2026-11-08', service_title: 'Leak repair', client: { full_name: 'Jamie Doe' }, rating: 5, is_visible: true } ] } });
  renderWithProviders(<ReviewListPage />);
  expect(screen.getByRole('link', { name: /Booking WEB-0012/ })).toHaveAttribute('href', '/provider/bookings/12');
  expect(screen.getByText('Leak repair')).toBeInTheDocument();
  expect(screen.getByText(/2026-11-08/)).toBeInTheDocument();
});
