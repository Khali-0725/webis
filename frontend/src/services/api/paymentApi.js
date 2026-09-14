import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const paymentApi = {
  /** GET /api/bookings/{id}/payment */
  async getForBooking(bookingId) {
    const response = await api.get(`/bookings/${bookingId}/payment`);
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/payment/proof (multipart) */
  async submitProof(bookingId, { proof, reference_number }) {
    const formData = new FormData();
    formData.append('proof', proof);
    if (reference_number) formData.append('reference_number', reference_number);

    const response = await api.post(`/bookings/${bookingId}/payment/proof`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return unwrap(response);
  },

  /** POST /api/payments/{id}/verify */
  async verify(paymentId) {
    const response = await api.post(`/payments/${paymentId}/verify`);
    return unwrap(response);
  },

  /** POST /api/payments/{id}/reject */
  async reject(paymentId, reason) {
    const response = await api.post(`/payments/${paymentId}/reject`, { reason });
    return unwrap(response);
  },

  /** GET /api/payments */
  async list(params = {}) {
    const response = await api.get('/payments', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** DELETE /api/payments/{id} (admin, soft delete) */
  async remove(paymentId) {
    const response = await api.delete(`/payments/${paymentId}`);
    return unwrap(response);
  },

  /** POST /api/payments/{id}/restore (admin) */
  async restore(paymentId) {
    const response = await api.post(`/payments/${paymentId}/restore`);
    return unwrap(response);
  },
};
