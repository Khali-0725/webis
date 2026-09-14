import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const reportApi = {
  /** POST /api/reports */
  async submit({ reportable_type, reportable_id, reason, details }) {
    const response = await api.post('/reports', { reportable_type, reportable_id, reason, details });
    return unwrap(response);
  },

  /** GET /api/reports - my own reports */
  async list(params = {}) {
    const response = await api.get('/reports', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** PATCH /api/reports/{id} - edit while still open */
  async update(id, payload) {
    const response = await api.patch(`/reports/${id}`, payload);
    return unwrap(response);
  },

  /** DELETE /api/reports/{id} - withdraw while still open */
  async remove(id) {
    const response = await api.delete(`/reports/${id}`);
    return unwrap(response);
  },
};
