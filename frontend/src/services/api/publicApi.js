import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const publicApi = {
  /** GET /api/barangays */
  async listBarangays() {
    const response = await api.get('/barangays');
    return unwrap(response);
  },

  /** GET /api/service-categories */
  async listCategories() {
    const response = await api.get('/service-categories');
    return unwrap(response);
  },

  /** GET /api/services */
  async searchServices(params = {}) {
    const response = await api.get('/services', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** GET /api/services/{id} */
  async getService(id) {
    const response = await api.get(`/services/${id}`);
    return unwrap(response);
  },

  /** GET /api/providers/{id} */
  async getProvider(id) {
    const response = await api.get(`/providers/${id}`);
    return unwrap(response);
  },

  /** GET /api/providers */
  async listProviders(params = {}) {
    const response = await api.get('/providers', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },
};
