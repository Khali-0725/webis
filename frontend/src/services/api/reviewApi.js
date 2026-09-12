import { api, unwrap, unwrapMeta } from '@/services/api/client';

export const reviewApi = {
  /** GET /api/bookings/{id}/review */
  async forBooking(bookingId) {
    const response = await api.get(`/bookings/${bookingId}/review`);
    return unwrap(response);
  },

  /** POST /api/bookings/{id}/review */
  async submit(bookingId, { rating, comment }) {
    const response = await api.post(`/bookings/${bookingId}/review`, { rating, comment });
    return unwrap(response);
  },

  /** GET /api/me/reviews */
  async myReviews(params = {}) {
    const response = await api.get('/me/reviews', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** GET /api/provider/reviews */
  async providerReviews(params = {}) {
    const response = await api.get('/provider/reviews', { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** GET /api/providers/{id}/reviews */
  async forProvider(providerProfileId, params = {}) {
    const response = await api.get(`/providers/${providerProfileId}/reviews`, { params });
    return { items: unwrap(response), meta: unwrapMeta(response) };
  },

  /** POST /api/reviews/{id}/reply */
  async reply(reviewId, providerReply) {
    const response = await api.post(`/reviews/${reviewId}/reply`, { provider_reply: providerReply });
    return unwrap(response);
  },

  /** PATCH /api/reviews/{id}/visibility */
  async setVisibility(reviewId, isVisible) {
    const response = await api.patch(`/reviews/${reviewId}/visibility`, { is_visible: isVisible });
    return unwrap(response);
  },
};
