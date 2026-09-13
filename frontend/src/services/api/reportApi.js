import { api, unwrap } from '@/services/api/client';

export const reportApi = {
  /** POST /api/reports */
  async submit({ reportable_type, reportable_id, reason, details }) {
    const response = await api.post('/reports', { reportable_type, reportable_id, reason, details });
    return unwrap(response);
  },
};
