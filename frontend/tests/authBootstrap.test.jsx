import { describe, it, expect, beforeEach, vi } from 'vitest';
import { StrictMode } from 'react';
import { screen, waitFor } from '@testing-library/react';
import { useAuthBootstrap, useAuth } from '@/hooks/useAuth';
import { authApi } from '@/services/auth/authApi';
import { useAuthStore } from '@/store/authStore';
import { renderWithProviders, resetSession, makeUser } from './utils';

vi.mock('@/services/auth/authApi', () => ({
  authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn(), register: vi.fn() },
}));

/** Mirrors <AuthBootstrap> + a consumer, which is how the real app wires it. */
function Harness() {
  useAuthBootstrap();
  const { isReady, isAuthenticated, user } = useAuth();

  if (!isReady) return <p>restoring session</p>;

  return <p>{isAuthenticated ? `signed in as ${user.email}` : 'signed out'}</p>;
}

describe('useAuthBootstrap', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
  });

  // Regression test. The first implementation depended on the store's `status`
  // and cancelled its request from the effect cleanup, so setStatus('loading')
  // re-ran the effect, the cleanup cancelled the in-flight request, and the
  // result was never applied - the app hung on the spinner forever.
  it('reaches a ready state when the session belongs to someone', async () => {
    authApi.me.mockResolvedValue(makeUser());

    renderWithProviders(<Harness />);

    expect(screen.getByText('restoring session')).toBeInTheDocument();

    expect(await screen.findByText('signed in as client@webis.test')).toBeInTheDocument();
    expect(useAuthStore.getState().status).toBe('ready');
  });

  it('reaches a ready state when there is no session', async () => {
    authApi.me.mockResolvedValue(null);

    renderWithProviders(<Harness />);

    expect(await screen.findByText('signed out')).toBeInTheDocument();
    expect(useAuthStore.getState().status).toBe('ready');
  });

  it('reaches a ready state when the API is unreachable', async () => {
    authApi.me.mockRejectedValue({ status: 0, isNetworkError: true, message: 'down', errors: {} });

    renderWithProviders(<Harness />);

    expect(await screen.findByText('signed out')).toBeInTheDocument();
    expect(useAuthStore.getState().status).toBe('ready');
  });

  it('sends exactly one request, even under StrictMode double-invoke', async () => {
    authApi.me.mockResolvedValue(makeUser());

    renderWithProviders(
      <StrictMode>
        <Harness />
      </StrictMode>,
    );

    await screen.findByText('signed in as client@webis.test');

    await waitFor(() => expect(authApi.me).toHaveBeenCalledTimes(1));
  });

  it('never leaves the app stuck on the loading state', async () => {
    authApi.me.mockResolvedValue(makeUser());

    renderWithProviders(<Harness />);

    await waitFor(() => expect(useAuthStore.getState().status).toBe('ready'));
    expect(screen.queryByText('restoring session')).not.toBeInTheDocument();
  });
});
