import { useCallback, useState } from 'react';
import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Button } from '@/components/ui/Button';

// Leaflet's default marker image paths assume a specific bundler layout that
// Vite does not reproduce. A plain SVG divIcon sidesteps the broken asset
// path entirely instead of wiring up leaflet's PNG marker images.
const PIN_ICON = L.divIcon({
  className: '',
  html: `<svg width="30" height="30" viewBox="0 0 24 24" fill="#d1471f" stroke="white" stroke-width="1.2">
    <path d="M12 21s7-6.3 7-11a7 7 0 10-14 0c0 4.7 7 11 7 11z"/>
    <circle cx="12" cy="10" r="2.5" fill="white" stroke="none"/>
  </svg>`,
  iconSize: [30, 30],
  iconAnchor: [15, 28],
});

const TANZA_CENTER = [14.3292, 120.8553];

function ClickToPlace({ onPick }) {
  useMapEvents({
    click(event) {
      onPick(event.latlng.lat, event.latlng.lng);
    },
  });

  return null;
}

/**
 * A pin-drop location picker. No API key: OpenStreetMap raster tiles, per the
 * thesis scope note (manual pin placement, not a mapping-API integration).
 */
export function LocationPicker({ latitude, longitude, onChange, height = 260 }) {
  const [locating, setLocating] = useState(false);
  const hasPin = latitude != null && longitude != null;
  const center = hasPin ? [latitude, longitude] : TANZA_CENTER;

  const handlePick = useCallback(
    (lat, lng) => onChange(Number(lat.toFixed(7)), Number(lng.toFixed(7))),
    [onChange],
  );

  const useMyLocation = () => {
    if (!navigator.geolocation) return;

    setLocating(true);
    navigator.geolocation.getCurrentPosition(
      (position) => {
        handlePick(position.coords.latitude, position.coords.longitude);
        setLocating(false);
      },
      () => setLocating(false),
      { timeout: 8000 },
    );
  };

  return (
    <div className="space-y-2">
      <div className="overflow-hidden rounded-lg border border-line" style={{ height }}>
        <MapContainer center={center} zoom={hasPin ? 16 : 13} style={{ height: '100%', width: '100%' }}>
          <TileLayer
            attribution="&copy; OpenStreetMap contributors"
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <ClickToPlace onPick={handlePick} />
          {hasPin && <Marker position={[latitude, longitude]} icon={PIN_ICON} />}
        </MapContainer>
      </div>
      <div className="flex items-center justify-between">
        <p className="text-xs text-ink-muted">
          {hasPin ? `Pin set at ${latitude.toFixed(5)}, ${longitude.toFixed(5)}` : 'Tap the map to drop a pin.'}
        </p>
        <Button type="button" size="sm" variant="outline" onClick={useMyLocation} loading={locating}>
          Use my location
        </Button>
      </div>
    </div>
  );
}
