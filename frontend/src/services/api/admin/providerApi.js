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

  /** PATCH /api/admin/providers/{id} */
  async update(id, payload) {
    const response = await api.patch(`/admin/providers/${id}`, payload);
    return unwrap(response);
  },

  /** DELETE /api/admin/providers/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/admin/providers/${id}`);
    return unwrap(response);
  },

  /** POST /api/admin/providers/{id}/restore */
  async restore(id) {
    const response = await api.post(`/admin/providers/${id}/restore`);
    return unwrap(response);
  },
};
