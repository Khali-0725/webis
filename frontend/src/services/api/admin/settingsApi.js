import { api, unwrap } from '@/services/api/client';

export const settingsApi = {
  /** GET /api/admin/settings */
  async list() {
    const response = await api.get('/admin/settings');
    return unwrap(response) ?? [];
  },

  /** PUT /api/admin/settings */
  async update(settings) {
    const response = await api.put('/admin/settings', { settings });
    return unwrap(response);
  },
};
