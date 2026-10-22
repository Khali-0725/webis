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
  async suspend(id, payload) {
    const response = await api.patch(`/admin/users/${id}/suspend`, payload);
    return unwrap(response);
  },

  async terminate(id, reason) {
    const response = await api.patch(`/admin/users/${id}/terminate`, { reason });
    return unwrap(response);
  },

  async restoreTerminated(id, reason) {
    const response = await api.patch(`/admin/users/${id}/restore-terminated`, { reason });
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

  /** DELETE /api/admin/users/{id}/permanent (trashed users without linked history) */
  async permanentRemove(id) {
    const response = await api.delete(`/admin/users/${id}/permanent`);
    return unwrap(response);
  },

  /** POST /api/admin/users/{id}/restore */
  async restore(id) {
    const response = await api.post(`/admin/users/${id}/restore`);
    return unwrap(response);
  },
};
