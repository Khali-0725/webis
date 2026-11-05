import { api, unwrap } from '@/services/api/client';

export const faqApi = {
  async list() {
    const response = await api.get('/admin/faqs');
    return unwrap(response) ?? [];
  },

  async create(payload) {
    const response = await api.post('/admin/faqs', payload);
    return unwrap(response);
  },

  async update(id, payload) {
    const response = await api.patch(`/admin/faqs/${id}`, payload);
    return unwrap(response);
  },

  async remove(id) {
    await api.delete(`/admin/faqs/${id}`);
  },
};
