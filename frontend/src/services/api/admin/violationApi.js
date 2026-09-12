import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const violationApi = {
  /** GET /api/admin/chat-violations */
  async list(params = {}) {
    const response = await api.get('/admin/chat-violations', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },

  /** GET /api/admin/chat-violations/{id} */
  async show(id) {
    const response = await api.get(`/admin/chat-violations/${id}`);
    return unwrap(response);
  },

  /** POST /api/admin/chat-violations/{id}/{action} */
  async act(id, action) {
    const response = await api.post(`/admin/chat-violations/${id}/${action}`);
    return unwrap(response);
  },
};
