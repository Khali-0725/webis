import { api, unwrap } from '@/services/api/client';

export const earningsApi = {
  /** GET /api/provider/earnings/summary */
  async summary() {
    const response = await api.get('/provider/earnings/summary');
    return unwrap(response);
  },

  /** GET /api/provider/earnings/over-time */
  async overTime(params = {}) {
    const response = await api.get('/provider/earnings/over-time', { params });
    return unwrap(response) ?? [];
  },
};
