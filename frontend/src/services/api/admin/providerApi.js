import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const providerApi = {
  /** GET /api/admin/providers */
  async list(params = {}) {
    const response = await api.get('/admin/providers', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** GET /api/admin/providers/{id} */
  async show(id) {
    const response = await api.get(`/admin/providers/${id}`);
    return unwrap(response);
  },
};
