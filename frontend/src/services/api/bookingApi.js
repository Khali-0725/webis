import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const bookingApi = {
  /** POST /api/bookings */
  async create(payload) {
    const response = await api.post('/bookings', payload);
    return unwrap(response);
  },

  /** GET /api/bookings */
  async list(params = {}) {
    const response = await api.get('/bookings', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** GET /api/bookings/{id} */
  async get(id) {
    const response = await api.get(`/bookings/${id}`);
    return unwrap(response);
  },

  /** GET /api/bookings/{id}/location */
  async getLocation(id) {
    const response = await api.get(`/bookings/${id}/location`);
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/accept */
  async accept(id) {
    const response = await api.post(`/bookings/${id}/accept`);
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/reject */
  async reject(id, reason) {
    const response = await api.post(`/bookings/${id}/reject`, { reason });
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/cancel */
  async cancel(id, reason) {
    const response = await api.post(`/bookings/${id}/cancel`, { reason });
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/status */
  async updateStatus(id, to) {
    const response = await api.post(`/bookings/${id}/status`, { to });
    return unwrap(response);
  },

  /** GET /api/providers/{id}/availability?date= */
  async getAvailability(providerId, date) {
    const response = await api.get(`/providers/${providerId}/availability`, { params: { date } });
    return unwrap(response);
  },

  /** PATCH /api/bookings/{id} - client edits notes/slot while pending */
  async update(id, payload) {
    const response = await api.patch(`/bookings/${id}`, payload);
    return unwrap(response);
  },

  /** DELETE /api/bookings/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/bookings/${id}`);
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/restore (admin) */
  async restore(id) {
    const response = await api.post(`/bookings/${id}/restore`);
    return unwrap(response);
  },
};
