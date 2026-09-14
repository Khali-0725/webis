import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const userApi = {
  /** GET /api/admin/users */
  async list(params = {}) {
    const response = await api.get('/admin/users', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** GET /api/admin/users/{id} */
  async show(id) {
    const response = await api.get(`/admin/users/${id}`);
    return unwrap(response);
  },

  /** PATCH /api/admin/users/{id}/suspend */
  async suspend(id) {
    const response = await api.patch(`/admin/users/${id}/suspend`);
    return unwrap(response);
  },

  /** PATCH /api/admin/users/{id}/activate */
  async activate(id) {
    const response = await api.patch(`/admin/users/${id}/activate`);
    return unwrap(response);
  },

  /** POST /api/admin/users */
  async create(payload) {
    const response = await api.post('/admin/users', payload);
    return unwrap(response);
  },

  /** PATCH /api/admin/users/{id} */
  async update(id, payload) {
    const response = await api.patch(`/admin/users/${id}`, payload);
    return unwrap(response);
  },

  /** DELETE /api/admin/users/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/admin/users/${id}`);
    return unwrap(response);
  },

  /** POST /api/admin/users/{id}/restore */
  async restore(id) {
    const response = await api.post(`/admin/users/${id}/restore`);
    return unwrap(response);
  },
};
