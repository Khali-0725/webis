import { api, unwrap } from '@/services/api/client';

export const analyticsApi = {
  /** GET /api/admin/analytics/summary */
  async summary() {
    const response = await api.get('/admin/analytics/summary');
    return unwrap(response);
  },

  /** GET /api/admin/analytics/bookings-over-time */
  async bookingsOverTime(params = {}) {
    const response = await api.get('/admin/analytics/bookings-over-time', { params });
    return unwrap(response) ?? [];
  },

  /** GET /api/admin/analytics/earnings-over-time */
  async earningsOverTime(params = {}) {
    const response = await api.get('/admin/analytics/earnings-over-time', { params });
    return unwrap(response) ?? [];
  },

  /** GET /api/admin/analytics/bookings-by-category */
  async bookingsByCategory() {
    const response = await api.get('/admin/analytics/bookings-by-category');
    return unwrap(response) ?? [];
  },

  /** GET /api/admin/analytics/top-providers */
  async topProviders() {
    const response = await api.get('/admin/analytics/top-providers');
    return unwrap(response);
  },

  /** GET /api/admin/analytics/payment-summary */
  async paymentSummary() {
    const response = await api.get('/admin/analytics/payment-summary');
    return unwrap(response) ?? [];
  },
};
