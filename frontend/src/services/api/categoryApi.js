import { api, unwrap } from '@/services/api/client';

export const categoryApi = {
  /** GET /api/admin/categories */
  async list() {
    const response = await api.get('/admin/categories');
    return unwrap(response);
  },

  /** POST /api/admin/categories */
  async create(payload) {
    const response = await api.post('/admin/categories', payload);
    return unwrap(response);
  },

  /** PATCH /api/admin/categories/{id} */
  async update(id, payload) {
    const response = await api.patch(`/admin/categories/${id}`, payload);
    return unwrap(response);
  },

  /** PATCH /api/admin/categories/{id}/toggle */
  async toggle(id) {
    const response = await api.patch(`/admin/categories/${id}/toggle`);
    return unwrap(response);
  },
};
