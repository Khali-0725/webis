import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const appealApi = {
  async submit(payload) { const response = await api.post('/appeals', payload); return unwrap(response); },
  async status(credentials) { const response = await api.post('/appeals/status', credentials); return unwrap(response); },
  async list(params = {}) { const response = await api.get('/admin/appeals', { params }); return { items: unwrap(response), meta: unwrapMeta(response) }; },
  async decide(id, payload) { const response = await api.patch(`/admin/appeals/${id}`, payload); return unwrap(response); },
};
