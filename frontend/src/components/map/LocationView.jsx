import { MapContainer, Marker, TileLayer } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const PIN_ICON = L.divIcon({
  className: '',
  html: `<svg width="30" height="30" viewBox="0 0 24 24" fill="#d1471f" stroke="white" stroke-width="1.2">
    <path d="M12 21s7-6.3 7-11a7 7 0 10-14 0c0 4.7 7 11 7 11z"/>
    <circle cx="12" cy="10" r="2.5" fill="white" stroke="none"/>
  </svg>`,
  iconSize: [30, 30],
  iconAnchor: [15, 28],
});

/** Read-only pin display for an already-set location (no click handling). */
export function LocationView({ latitude, longitude, height = 220 }) {
  return (
    <div className="overflow-hidden rounded-lg border border-line" style={{ height }}>
      <MapContainer
        center={[latitude, longitude]}
        zoom={16}
        style={{ height: '100%', width: '100%' }}
        dragging={false}
        scrollWheelZoom={false}
        doubleClickZoom={false}
      >
        <TileLayer
          attribution="&copy; OpenStreetMap contributors"
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />
        <Marker position={[latitude, longitude]} icon={PIN_ICON} />
      </MapContainer>
    </div>
  );
}
