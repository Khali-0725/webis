import { describe, expect, it, vi } from 'vitest';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { LocationPicker } from '@/components/map/LocationPicker';

vi.mock('react-leaflet', () => ({
  MapContainer: () => <div data-testid="map" />,
  Marker: () => null,
  TileLayer: () => null,
  Circle: () => null,
  useMap: vi.fn(),
  useMapEvents: vi.fn(),
}));

describe('location picker GPS result', () => {
  it('uses the latest barangay selection when a pending GPS lookup finishes', async () => {
    const previousGeolocation = navigator.geolocation;
    let resolveLocation;
    Object.defineProperty(navigator, 'geolocation', {
      configurable: true,
      value: { getCurrentPosition: vi.fn((success) => { resolveLocation = success; }) },
    });

    try {
      const oldSelection = vi.fn();
      const newSelection = vi.fn();
      const { rerender } = render(<LocationPicker latitude={null} longitude={null} onChange={oldSelection}
        suggestedCenter={{ latitude: 14.326, longitude: 120.853 }} />);

      await userEvent.setup().click(screen.getByRole('button', { name: 'Use my location' }));
      rerender(<LocationPicker latitude={null} longitude={null} onChange={newSelection}
        suggestedCenter={{ latitude: 14.352, longitude: 120.866 }} />);
      act(() => resolveLocation({ coords: { latitude: 14.326, longitude: 120.853 } }));

      expect(oldSelection).not.toHaveBeenCalled();
      expect(newSelection).toHaveBeenCalledWith(14.326, 120.853);
    } finally {
      Object.defineProperty(navigator, 'geolocation', { configurable: true, value: previousGeolocation });
    }
  });
});
