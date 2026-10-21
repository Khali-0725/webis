import { api, unwrap } from '@/services/api/client';

export const bulkArchiveApi = {
  async archive(resource, ids, reason) {
    const response = await api.post(`/admin/records/${resource}/bulk-archive`, {
      ids,
      ...(reason ? { reason } : {}),
    });
    return unwrap(response);
  },
};
