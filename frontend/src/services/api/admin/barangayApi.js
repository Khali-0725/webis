import { api, unwrap } from '@/services/api/client';

export const adminBarangayApi = {
  /** GET /api/admin/barangays */
  async list(params = {}) {
    const response = await api.get('/admin/barangays', { params });
    return unwrap(response) ?? [];
  },

  /** POST /api/admin/barangays */
  async create(payload) {
    const response = await api.post('/admin/barangays', payload);
    return unwrap(response);
  },

  /** PATCH /api/admin/barangays/{id} */
  async update(id, payload) {
    const response = await api.patch(`/admin/barangays/${id}`, payload);
    return unwrap(response);
  },

  /** PATCH /api/admin/barangays/{id}/toggle */
  async toggle(id) {
    const response = await api.patch(`/admin/barangays/${id}/toggle`);
    return unwrap(response);
  },

  /** DELETE /api/admin/barangays/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/admin/barangays/${id}`);
    return unwrap(response);
  },

  /** POST /api/admin/barangays/{id}/restore */
  async restore(id) {
    const response = await api.post(`/admin/barangays/${id}/restore`);
    return unwrap(response);
  },
};
