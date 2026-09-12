import { describe, it, expect, beforeEach, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LoginPage from '@/pages/auth/LoginPage';
import { authApi } from '@/services/auth/authApi';
import { renderWithProviders, resetSession, makeUser } from './utils';

vi.mock('@/services/auth/authApi', () => ({
  authApi: {
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
  },
}));

describe('LoginPage', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
  });

  it('renders the WEBIS sign-in form', () => {
    renderWithProviders(<LoginPage />);

    expect(screen.getByRole('heading', { name: 'Welcome' })).toBeInTheDocument();
    expect(screen.getByLabelText(/email address/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Login' })).toBeInTheDocument();
  });

  it('blocks submission and shows messages when fields are empty', async () => {
    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.click(screen.getByRole('button', { name: 'Login' }));

    expect(await screen.findByText('Email address is required.')).toBeInTheDocument();
    expect(screen.getByText('Password is required.')).toBeInTheDocument();
    expect(authApi.login).not.toHaveBeenCalled();
  });

  it('rejects a malformed email before calling the API', async () => {
    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText(/email address/i), 'not-an-email');
    await user.type(screen.getByLabelText(/password/i), 'Password123');
    await user.click(screen.getByRole('button', { name: 'Login' }));

    expect(await screen.findByText('Enter a valid email address.')).toBeInTheDocument();
    expect(authApi.login).not.toHaveBeenCalled();
  });

  it('submits valid credentials to the API', async () => {
    authApi.login.mockResolvedValue(makeUser());
    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText(/email address/i), 'client@webis.test');
    await user.type(screen.getByLabelText(/password/i), 'Password123');
    await user.click(screen.getByRole('button', { name: 'Login' }));

    // React Query v5 passes a context object as a second argument to
    // mutationFn, so assert on the variables argument specifically.
    await waitFor(() => expect(authApi.login).toHaveBeenCalledTimes(1));

    expect(authApi.login.mock.calls[0][0]).toEqual({
      email: 'client@webis.test',
      password: 'Password123',
      remember: false,
    });
  });

  it('surfaces a server field error against the field', async () => {
    authApi.login.mockRejectedValue({
      status: 422,
      message: 'The submitted data is invalid.',
      errors: { email: ['These credentials do not match our records.'] },
    });

    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText(/email address/i), 'client@webis.test');
    await user.type(screen.getByLabelText(/password/i), 'WrongPassword1');
    await user.click(screen.getByRole('button', { name: 'Login' }));

    expect(
      await screen.findByText('These credentials do not match our records.'),
    ).toBeInTheDocument();
  });

  it('shows a banner for a non-field failure such as a suspended account', async () => {
    authApi.login.mockRejectedValue({
      status: 403,
      message: 'This account has been suspended. Please contact the WEBIS administrator.',
      errors: {},
    });

    const user = userEvent.setup();
    renderWithProviders(<LoginPage />);

    await user.type(screen.getByLabelText(/email address/i), 'banned@webis.test');
    await user.type(screen.getByLabelText(/password/i), 'Password123');
    await user.click(screen.getByRole('button', { name: 'Login' }));

    expect(await screen.findByRole('alert')).toHaveTextContent(/suspended/i);
  });
});
