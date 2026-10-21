import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const notificationApi = {
  async list(params = {}) {
    const response = await api.get('/notifications', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },
  async markRead(id) {
    const response = await api.patch(`/notifications/${id}/read`);
    return unwrap(response);
  },
};
