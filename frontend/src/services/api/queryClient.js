import { QueryClient } from '@tanstack/react-query';

/**
 * Shared query client.
 *
 * A 401 is never retried: it means the session is gone, and hammering the API
 * only produces more 401s. Same for 403/404/422 - those are decisions, not
 * transient failures.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error) => {
        const status = error?.status ?? 0;

        if ([401, 403, 404, 422].includes(status)) return false;

        return failureCount < 2;
      },
      staleTime: 30_000,
      refetchOnWindowFocus: false,
    },
    mutations: {
      retry: false,
    },
  },
});

/** Query keys live in one place so invalidation can never drift. */
export const queryKeys = {
  auth: {
    me: ['auth', 'me'],
  },
  health: ['health'],
  admin: {
    users: (params) => ['admin', 'users', params],
    user: (id) => ['admin', 'users', id],
    verificationDocuments: (status) => ['admin', 'verification-documents', status],
    categories: ['admin', 'categories'],
    providers: (params) => ['admin', 'providers', params],
    provider: (id) => ['admin', 'providers', id],
    services: (params) => ['admin', 'services', params],
    barangays: ['admin', 'barangays'],
    violations: (params) => ['admin', 'violations', params],
    violation: (id) => ['admin', 'violations', id],
    reports: (params) => ['admin', 'reports', params],
    auditLogs: (params) => ['admin', 'audit-logs', params],
    settings: ['admin', 'settings'],
    analyticsSummary: ['admin', 'analytics', 'summary'],
    analyticsBookingsOverTime: ['admin', 'analytics', 'bookings-over-time'],
    analyticsEarningsOverTime: ['admin', 'analytics', 'earnings-over-time'],
    analyticsBookingsByCategory: ['admin', 'analytics', 'bookings-by-category'],
    analyticsTopProviders: ['admin', 'analytics', 'top-providers'],
    analyticsPaymentSummary: ['admin', 'analytics', 'payment-summary'],
  },
  public: {
    barangays: ['barangays'],
    categories: ['categories'],
    services: (params) => ['services', params],
    service: (id) => ['services', id],
    providers: (params) => ['providers', params],
    provider: (id) => ['providers', id],
    providerReviews: (id, params) => ['providers', id, 'reviews', params],
  },
  provider: {
    profile: ['provider', 'profile'],
    services: ['provider', 'services'],
    availabilityRules: ['provider', 'availability', 'rules'],
    availabilityExceptions: ['provider', 'availability', 'exceptions'],
    paymentMethods: ['provider', 'payment-methods'],
    reviews: (params) => ['provider', 'reviews', params],
    earningsSummary: ['provider', 'earnings', 'summary'],
    earningsOverTime: ['provider', 'earnings', 'over-time'],
  },
  bookings: {
    list: (params) => ['bookings', params],
    detail: (id) => ['bookings', id],
    availability: (providerId, date) => ['bookings', 'availability', providerId, date],
  },
  conversations: {
    list: ['conversations'],
    messages: (id) => ['conversations', id, 'messages'],
    unreadCount: ['conversations', 'unread-count'],
  },
  payments: {
    forBooking: (bookingId) => ['payments', 'booking', bookingId],
    list: (params) => ['payments', params],
  },
  reviews: {
    mine: (params) => ['reviews', 'mine', params],
    forBooking: (bookingId) => ['reviews', 'booking', bookingId],
  },
};
