import { render } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useAuthStore } from '@/store/authStore';

export function makeUser(overrides = {}) {
  return {
    id: 1,
    first_name: 'Demo',
    last_name: 'Client',
    full_name: 'Demo Client',
    initials: 'DC',
    email: 'client@webis.test',
    phone: '09171234567',
    avatar_url: null,
    role: 'client',
    role_label: 'Client',
    status: 'active',
    email_verified: true,
    home_path: '/client/dashboard',
    created_at: '2026-01-01T00:00:00+08:00',
    ...overrides,
  };
}

/** Puts the auth store into a known state before a test renders. */
export function setSession(user) {
  if (user) {
    useAuthStore.getState().setUser(user);
  } else {
    useAuthStore.getState().clearUser();
  }
}

export function resetSession() {
  useAuthStore.setState({ user: null, status: 'idle' });
}

export function renderWithProviders(ui, { route = '/', client } = {}) {
  const queryClient =
    client ??
    new QueryClient({
      defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
    });

  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter initialEntries={[route]}>{ui}</MemoryRouter>
    </QueryClientProvider>,
  );
}
