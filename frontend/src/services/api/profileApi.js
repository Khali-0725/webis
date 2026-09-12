import { api, unwrap } from '@/services/api/client';

export const profileApi = {
  /** PATCH /api/me/profile */
  async updateProfile(payload) {
    const response = await api.patch('/me/profile', payload);
    return unwrap(response);
  },

  /** POST /api/me/avatar */
  async updateAvatar(formData) {
    const response = await api.post('/me/avatar', formData);
    return unwrap(response);
  },
};
