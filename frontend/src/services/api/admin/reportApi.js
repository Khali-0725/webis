import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const adminReportApi = {
  /** GET /api/admin/reports */
  async list(params = {}) {
    const response = await api.get('/admin/reports', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** POST /api/admin/reports/{id}/resolve */
  async resolve(id, payload) {
    const response = await api.post(`/admin/reports/${id}/resolve`, payload);
    return unwrap(response);
  },

  /** DELETE /api/admin/reports/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/admin/reports/${id}`);
    return unwrap(response);
  },

  /** POST /api/admin/reports/{id}/restore */
  async restore(id) {
    const response = await api.post(`/admin/reports/${id}/restore`);
    return unwrap(response);
  },
};
