import { api, unwrap } from '@/services/api/client';

export const earningsApi = {
  /** GET /api/provider/earnings/summary */
  async summary(params = {}) {
    const response = await api.get('/provider/earnings/summary', { params });
    return unwrap(response);
  },

  /** GET /api/provider/earnings/over-time */
  async overTime(params = {}) {
    const response = await api.get('/provider/earnings/over-time', { params });
    return unwrap(response) ?? [];
  },
  async transactions(params = {}) {
    const response = await api.get('/provider/earnings/transactions', { params });
    return unwrap(response) ?? [];
  },
};
