import { describe, expect, it } from 'vitest';
import { dashboardBackLink } from '@/utils/dashboardBackLink';

describe('dashboard back link', () => {
  it('returns to the client booking list from a booking detail', () => {
    expect(dashboardBackLink('/client/bookings/12', '/client/dashboard')).toEqual({
      to: '/client/bookings', label: 'Back to booking',
    });
  });

  it('does not link a booking list to itself', () => {
    expect(dashboardBackLink('/provider/bookings', '/provider/dashboard')).toBeNull();
  });

  it('keeps the dashboard link elsewhere', () => {
    expect(dashboardBackLink('/provider/availability', '/provider/dashboard')).toEqual({
      to: '/provider/dashboard', label: 'Back to Dashboard',
    });
  });

  it('returns nested service and message screens to their parent modules', () => {
    expect(dashboardBackLink('/provider/services/42/edit', '/provider/dashboard')).toEqual({
      to: '/provider/services', label: 'Back to services',
    });
    expect(dashboardBackLink('/client/messages/7', '/client/dashboard')).toEqual({
      to: '/client/messages', label: 'Back to messages',
    });
  });

  it('returns client search details to the search module', () => {
    expect(dashboardBackLink('/client/providers/5', '/client/dashboard')).toEqual({
      to: '/client/providers', label: 'Back to providers',
    });
    expect(dashboardBackLink('/client/services/9', '/client/dashboard')).toEqual({
      to: '/client/search', label: 'Back to search',
    });
  });
});
