import { api, unwrap, resetCsrfCookie } from '@/services/api/client';

export const authApi = {
  /** POST /api/auth/register */
  async register(payload) {
    const response = await api.post('/auth/register', payload);

    return unwrap(response);
  },

  /** POST /api/auth/login */
  async login({ email, password, remember = false }) {
    const response = await api.post('/auth/login', { email, password, remember });

    return unwrap(response);
  },

  /**
   * POST /api/auth/google
   *
   * `role` is only meaningful the first time this Google identity signs in -
   * it decides the role of the account *if one has to be created*. Omit it
   * (LoginPage) to only ever sign in to an existing account.
   */
  async google({ credential, role }) {
    const response = await api.post('/auth/google', { credential, role });

    return unwrap(response);
  },

  /** POST /api/auth/logout */
  async logout() {
    await api.post('/auth/logout');
    resetCsrfCookie();
  },

  /** POST /api/auth/forgot-password */
  async forgotPassword({ email }) {
    const response = await api.post('/auth/forgot-password', { email });

    return unwrap(response);
  },

  /** POST /api/auth/reset-password */
  async resetPassword(payload) {
    const response = await api.post('/auth/reset-password', payload);

    return unwrap(response);
  },

  /**
   * GET /api/auth/me
   *
   * Called once on boot. A 401 here is the normal "not signed in" answer, not
   * an error, so it resolves to null rather than throwing.
   */
  async me() {
    try {
      const response = await api.get('/auth/me');

      return unwrap(response);
    } catch (error) {
      if (error?.status === 401) return null;

      throw error;
    }
  },
};
