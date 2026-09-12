import { describe, it, expect, beforeEach, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { Route, Routes } from 'react-router-dom';
import { ProtectedRoute, GuestRoute } from '@/routes/ProtectedRoute';
import { ROLES } from '@/constants';
import { renderWithProviders, setSession, resetSession, makeUser } from './utils';

// The API is never contacted in these tests - the store is seeded directly.
vi.mock('@/services/auth/authApi', () => ({
  authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn(), register: vi.fn() },
}));

function Harness() {
  return (
    <Routes>
      <Route path="/login" element={<p>Login page</p>} />
      <Route path="/client/dashboard" element={<p>Client home</p>} />
      <Route path="/provider/dashboard" element={<p>Provider home</p>} />

      <Route element={<ProtectedRoute allow={[ROLES.PROVIDER]} />}>
        <Route path="/provider/services" element={<p>Provider services</p>} />
      </Route>

      <Route element={<ProtectedRoute allow={[ROLES.ADMIN]} />}>
        <Route path="/admin/users" element={<p>Admin users</p>} />
      </Route>

      <Route element={<GuestRoute />}>
        <Route path="/register" element={<p>Register page</p>} />
      </Route>
    </Routes>
  );
}

describe('route protection', () => {
  beforeEach(() => resetSession());

  it('sends a signed-out visitor to the login page', async () => {
    setSession(null);
    renderWithProviders(<Harness />, { route: '/provider/services' });

    expect(await screen.findByText('Login page')).toBeInTheDocument();
    expect(screen.queryByText('Provider services')).not.toBeInTheDocument();
  });

  it('lets the correct role through', async () => {
    setSession(makeUser({ role: ROLES.PROVIDER, home_path: '/provider/dashboard' }));
    renderWithProviders(<Harness />, { route: '/provider/services' });

    expect(await screen.findByText('Provider services')).toBeInTheDocument();
  });

  it('redirects a client away from the admin portal to their own dashboard', async () => {
    setSession(makeUser({ role: ROLES.CLIENT, home_path: '/client/dashboard' }));
    renderWithProviders(<Harness />, { route: '/admin/users' });

    expect(await screen.findByText('Client home')).toBeInTheDocument();
    expect(screen.queryByText('Admin users')).not.toBeInTheDocument();
  });

  it('redirects a client away from the provider portal', async () => {
    setSession(makeUser({ role: ROLES.CLIENT, home_path: '/client/dashboard' }));
    renderWithProviders(<Harness />, { route: '/provider/services' });

    expect(await screen.findByText('Client home')).toBeInTheDocument();
  });

  it('keeps a signed-in user away from the register page', async () => {
    setSession(makeUser({ role: ROLES.CLIENT, home_path: '/client/dashboard' }));
    renderWithProviders(<Harness />, { route: '/register' });

    expect(await screen.findByText('Client home')).toBeInTheDocument();
  });

  it('shows a loading state until the session check finishes', () => {
    resetSession(); // status: 'idle'
    renderWithProviders(<Harness />, { route: '/provider/services' });

    expect(screen.getByRole('status')).toBeInTheDocument();
  });
});
