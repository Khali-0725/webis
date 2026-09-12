import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const auditLogApi = {
  /** GET /api/admin/audit-logs */
  async list(params = {}) {
    const response = await api.get('/admin/audit-logs', { params });
    return { items: unwrap(response) ?? [], meta: unwrapMeta(response) };
  },
};
