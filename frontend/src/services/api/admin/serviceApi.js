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

  /** PATCH /api/admin/services/{id} */
  async update(id, payload) {
    const response = await api.patch(`/admin/services/${id}`, payload);
    return unwrap(response);
  },

  /** DELETE /api/admin/services/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/admin/services/${id}`);
    return unwrap(response);
  },

  /** POST /api/admin/services/{id}/restore */
  async restore(id) {
    const response = await api.post(`/admin/services/${id}/restore`);
    return unwrap(response);
  },
};
