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
});
