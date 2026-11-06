import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage';
import { authApi } from '@/services/auth/authApi';
import { renderWithProviders, resetSession } from './utils';

vi.mock('@/services/auth/authApi', () => ({
  authApi: {
    forgotPassword: vi.fn(),
    verifyResetCode: vi.fn(),
    resetPassword: vi.fn(),
    me: vi.fn(),
  },
}));

describe('email OTP password reset', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
  });

  it('moves from email to code to password after verification', async () => {
    authApi.forgotPassword.mockResolvedValue(null);
    authApi.verifyResetCode.mockResolvedValue({ grant: 'a'.repeat(64) });
    authApi.resetPassword.mockResolvedValue(null);
    const user = userEvent.setup();
    renderWithProviders(<ForgotPasswordPage />);

    await user.type(screen.getByLabelText(/email address/i), 'client@webis.test');
    await user.click(screen.getByRole('button', { name: 'Send code' }));
    expect(await screen.findByLabelText(/six-digit code/i)).toBeInTheDocument();

    await user.type(screen.getByLabelText(/six-digit code/i), '123456');
    await user.click(screen.getByRole('button', { name: 'Verify code' }));
    expect(await screen.findByLabelText(/^new password/i)).toBeInTheDocument();

    await user.type(screen.getByLabelText(/^new password/i), 'NewPassword123!');
    await user.type(screen.getByLabelText(/confirm new password/i, { selector: 'input' }), 'NewPassword123!');
    await user.click(screen.getByRole('button', { name: 'Reset password' }));

    await waitFor(() => expect(authApi.resetPassword).toHaveBeenCalled());
    expect(authApi.resetPassword.mock.calls[0][0]).toEqual({
      email: 'client@webis.test', grant: 'a'.repeat(64),
      password: 'NewPassword123!', password_confirmation: 'NewPassword123!',
    });
  });

  it('keeps the code step when verification fails', async () => {
    authApi.forgotPassword.mockResolvedValue(null);
    authApi.verifyResetCode.mockRejectedValue({ message: 'Invalid or expired code. Request a new one.' });
    const user = userEvent.setup();
    renderWithProviders(<ForgotPasswordPage />);

    await user.type(screen.getByLabelText(/email address/i), 'client@webis.test');
    await user.click(screen.getByRole('button', { name: 'Send code' }));
    await user.type(await screen.findByLabelText(/six-digit code/i), '999999');
    await user.click(screen.getByRole('button', { name: 'Verify code' }));

    expect(await screen.findByRole('alert')).toHaveTextContent('Invalid or expired code');
    expect(screen.getByLabelText(/six-digit code/i)).toBeInTheDocument();
  });
});
