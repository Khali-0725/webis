import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const adminServiceApi = {
  /** GET /api/admin/services */
  async list(params = {}) {
    const response = await api.get('/admin/services', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** PATCH /api/admin/services/{id}/toggle */
  async toggle(id) {
    const response = await api.patch(`/admin/services/${id}/toggle`);
    return unwrap(response);
  },
};
