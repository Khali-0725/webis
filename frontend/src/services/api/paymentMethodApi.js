import { api, unwrap } from '@/services/api/client';

function toFormData(payload) {
  const formData = new FormData();

  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
  });

  return formData;
}

export const paymentMethodApi = {
  /** GET /api/provider/payment-methods */
  async list() {
    const response = await api.get('/provider/payment-methods');
    return unwrap(response);
  },

  /** POST /api/provider/payment-methods (multipart) */
  async create(payload) {
    const response = await api.post('/provider/payment-methods', toFormData(payload), {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return unwrap(response);
  },

  /** PATCH /api/provider/payment-methods/{id} (multipart) */
  async update(id, payload) {
    const formData = toFormData(payload);
    formData.append('_method', 'PATCH');

    const response = await api.post(`/provider/payment-methods/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return unwrap(response);
  },

  /** PATCH /api/provider/payment-methods/{id}/toggle */
  async toggle(id) {
    const response = await api.patch(`/provider/payment-methods/${id}/toggle`);
    return unwrap(response);
  },

  /** DELETE /api/provider/payment-methods/{id} (soft delete) */
  async remove(id) {
    const response = await api.delete(`/provider/payment-methods/${id}`);
    return unwrap(response);
  },
};
