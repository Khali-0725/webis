import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const conversationApi = {
  /** POST /api/conversations */
  async start(payload) {
    const response = await api.post('/conversations', payload);
    return unwrap(response);
  },

  /** GET /api/conversations */
  async list() {
    const response = await api.get('/conversations');
    return unwrap(response);
  },

  /** GET /api/conversations/{id}/messages */
  async listMessages(id, params = {}) {
    const response = await api.get(`/conversations/${id}/messages`, { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** POST /api/conversations/{id}/messages */
  async sendMessage(id, { body, confirm_override } = {}) {
    const response = await api.post(`/conversations/${id}/messages`, { body, confirm_override });
    return unwrap(response);
  },

  /** POST /api/conversations/{id}/read */
  async markRead(id) {
    const response = await api.post(`/conversations/${id}/read`);
    return unwrap(response);
  },

  /** GET /api/messages/unread-count */
  async unreadCount() {
    const response = await api.get('/messages/unread-count');
    return unwrap(response);
  },

  /** PATCH /api/conversations/{id}/messages/{messageId} */
  async updateMessage(id, messageId, { body, confirm_override } = {}) {
    const response = await api.patch(`/conversations/${id}/messages/${messageId}`, { body, confirm_override });
    return unwrap(response);
  },

  /** DELETE /api/conversations/{id}/messages/{messageId} (unsend) */
  async deleteMessage(id, messageId) {
    const response = await api.delete(`/conversations/${id}/messages/${messageId}`);
    return unwrap(response);
  },
};
