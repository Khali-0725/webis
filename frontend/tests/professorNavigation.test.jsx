import { beforeEach, expect, it, vi } from 'vitest';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Sidebar } from '@/components/layout/Sidebar';
import { DashboardLayout } from '@/layouts/DashboardLayout';
import { AppRoutes } from '@/routes';
import { publicApi } from '@/services/api/publicApi';
import { notificationApi } from '@/services/api/notificationApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/services/api/publicApi', () => ({ publicApi: {
  listCategories: vi.fn(), listBarangays: vi.fn(), searchServices: vi.fn(),
} }));
vi.mock('@/services/api/notificationApi', () => ({ notificationApi: { list: vi.fn() } }));
vi.mock('@/pages/public/ServiceDetailPage', () => ({ default: () => <p>Client service detail</p> }));

beforeEach(() => {
  resetSession();
  vi.clearAllMocks();
  notificationApi.list.mockResolvedValue({ items: [], meta: { unread_count: 0 } });
  publicApi.listCategories.mockResolvedValue([]);
  publicApi.listBarangays.mockResolvedValue([]);
  publicApi.searchServices.mockResolvedValue({ items: [], meta: { total: 0 } });
});

it('groups admin destinations and keeps Profile out of the main navigation', async () => {
  setSession(makeUser({ role: 'admin', home_path: '/admin/dashboard' }));
  renderWithProviders(<Sidebar />);
  const nav = screen.getByRole('navigation', { name: 'Main navigation' });

  expect(within(nav).getByText('People')).toBeInTheDocument();
  expect(within(nav).getAllByText('Services')).toHaveLength(2);
  expect(within(nav).getByText('Operations')).toBeInTheDocument();
  expect(within(nav).getByText('Moderation')).toBeInTheDocument();
  expect(within(nav).getByText('People').closest('details')).not.toHaveAttribute('open');
  await userEvent.setup().click(within(nav).getByText('People'));
  expect(within(nav).getByRole('link', { name: 'Users' })).toHaveAttribute('href', '/admin/users');
  expect(within(nav).getByRole('link', { name: 'Appeals' })).toHaveAttribute('href', '/admin/appeals');
  expect(within(nav).queryByRole('link', { name: 'Profile' })).not.toBeInTheDocument();
});

it.each(['admin', 'super_admin'])('opens the current %s section and lets other sections expand independently', async (role) => {
  setSession(makeUser({ role, home_path: '/admin/dashboard' }));
  renderWithProviders(<Sidebar />, { route: '/admin/verification' });
  const nav = within(screen.getByRole('navigation', { name: 'Main navigation' }));
  const people = nav.getByText('People').closest('details');
  const system = nav.getByText('System').closest('details');
  expect(people).toHaveAttribute('open');
  expect(system).not.toHaveAttribute('open');
  expect(screen.getByRole('link', { name: 'Verifications' })).toHaveAttribute('aria-current', 'page');
  expect(screen.getByText('Overview').closest('details')).toBeNull();

  const user = userEvent.setup();
  await user.click(nav.getByText('System'));
  expect(system).toHaveAttribute('open');
  expect(people).toHaveAttribute('open');
  expect(screen.getByRole('link', { name: 'Platform Settings' })).toBeInTheDocument();
  await user.click(screen.getByText('People'));
  expect(people).not.toHaveAttribute('open');
  expect(system).toHaveAttribute('open');
});

it('makes the sidebar identity the only account Profile link', () => {
  setSession(makeUser({ role: 'client', home_path: '/client/dashboard' }));
  renderWithProviders(<DashboardLayout title="Dashboard" />);

  expect(screen.getByRole('link', { name: 'My profile' })).toHaveAttribute('href', '/profile');
  expect(screen.queryByRole('link', { name: 'Open profile settings' })).not.toBeInTheDocument();
});

it('removes Home and sends client Search Services to the client portal', () => {
  setSession(makeUser({ role: 'client', home_path: '/client/dashboard' }));
  renderWithProviders(<Sidebar />);
  const nav = screen.getByRole('navigation', { name: 'Main navigation' });

  expect(within(nav).getByRole('link', { name: 'Search Services' })).toHaveAttribute('href', '/client/search');
  expect(within(nav).queryByRole('link', { name: 'Home' })).not.toBeInTheDocument();
  expect(within(nav).queryByRole('link', { name: 'Profile' })).not.toBeInTheDocument();
  expect(screen.getByRole('link', { name: 'WEBIS dashboard' })).toHaveAttribute('href', '/client/dashboard');
  expect(screen.getByRole('link', { name: 'My profile' })).toHaveAttribute('href', '/profile');
});

it('renders client search and service detail inside dashboard routes', async () => {
  setSession(makeUser({ role: 'client', home_path: '/client/dashboard' }));
  const searchView = renderWithProviders(<AppRoutes />, { route: '/client/search' });
  expect(await screen.findByRole('heading', { name: 'Browse services' })).toBeInTheDocument();
  expect(screen.getByRole('navigation', { name: 'Main navigation' })).toBeInTheDocument();
  searchView.unmount();

  renderWithProviders(<AppRoutes />, { route: '/client/services/7' });
  expect(await screen.findByText('Client service detail')).toBeInTheDocument();
  expect(screen.getByRole('navigation', { name: 'Main navigation' })).toBeInTheDocument();
});
