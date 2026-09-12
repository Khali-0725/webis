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
};
