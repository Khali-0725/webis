import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const providerApi = {
  /** GET /api/provider/profile */
  async getMyProfile() {
    const response = await api.get('/provider/profile');
    return unwrap(response);
  },

  /** PATCH /api/provider/profile */
  async updateMyProfile(payload) {
    const response = await api.patch('/provider/profile', payload);
    return unwrap(response);
  },

  /** PUT /api/provider/skills */
  async updateMySkills(skills) {
    const response = await api.put('/provider/skills', { skills });
    return unwrap(response);
  },

  /** PUT /api/provider/service-areas */
  async updateMyServiceAreas(barangayIds) {
    const response = await api.put('/provider/service-areas', { barangay_ids: barangayIds });
    return unwrap(response);
  },

  /** GET /api/provider/services */
  async listMyServices(params = {}) {
    const response = await api.get('/provider/services', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** GET /api/provider/services/{id} */
  async getService(id) {
    const response = await api.get(`/provider/services/${id}`);
    return unwrap(response);
  },

  /** POST /api/provider/services */
  async createService(payload) {
    const response = await api.post('/provider/services', payload);
    return unwrap(response);
  },

  /** PATCH /api/provider/services/{id} */
  async updateService(id, payload) {
    const response = await api.patch(`/provider/services/${id}`, payload);
    return unwrap(response);
  },

  /** PATCH /api/provider/services/{id}/publish */
  async publishService(id) {
    const response = await api.patch(`/provider/services/${id}/publish`);
    return unwrap(response);
  },

  /** PATCH /api/provider/services/{id}/deactivate */
  async deactivateService(id) {
    const response = await api.patch(`/provider/services/${id}/deactivate`);
    return unwrap(response);
  },

  /** DELETE /api/provider/services/{id} (soft delete) */
  async deleteService(id) {
    const response = await api.delete(`/provider/services/${id}`);
    return unwrap(response);
  },

  /** POST /api/provider/services/{id}/restore */
  async restoreService(id) {
    const response = await api.post(`/provider/services/${id}/restore`);
    return unwrap(response);
  },
};
