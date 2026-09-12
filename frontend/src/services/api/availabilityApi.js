import { api, unwrap } from '@/services/api/client';

export const availabilityApi = {
  /** GET /api/provider/availability/rules */
  async getRules() {
    const response = await api.get('/provider/availability/rules');
    return unwrap(response);
  },

  /** PUT /api/provider/availability/rules */
  async updateRules(rules) {
    const response = await api.put('/provider/availability/rules', { rules });
    return unwrap(response);
  },

  /** GET /api/provider/availability/exceptions */
  async listExceptions() {
    const response = await api.get('/provider/availability/exceptions');
    return unwrap(response);
  },

  /** POST /api/provider/availability/exceptions */
  async createException(payload) {
    const response = await api.post('/provider/availability/exceptions', payload);
    return unwrap(response);
  },

  /** DELETE /api/provider/availability/exceptions/{id} */
  async deleteException(id) {
    const response = await api.delete(`/provider/availability/exceptions/${id}`);
    return unwrap(response);
  },
};
