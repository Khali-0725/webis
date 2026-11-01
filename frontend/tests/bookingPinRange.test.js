import { describe, expect, it } from 'vitest';
import { isWithinBookingPinRange } from '@/utils/bookingPinRange';

const mulawin = { latitude: 14.326, longitude: 120.853 };

describe('booking pin range', () => {
  it('allows nearby pins and rejects distant or unlocated pins', () => {
    expect(isWithinBookingPinRange(14.33, 120.853, mulawin)).toBe(true);
    expect(isWithinBookingPinRange(14.352, 120.866, mulawin)).toBe(false);
    expect(isWithinBookingPinRange(39.9, 116.4, mulawin)).toBe(false);
    expect(isWithinBookingPinRange(14.326, 120.853, null)).toBe(false);
  });
});
