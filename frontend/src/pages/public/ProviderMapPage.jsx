import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { MapContainer, Marker, Popup, TileLayer, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useQuery } from '@tanstack/react-query';
import { publicApi } from '@/services/api/publicApi';
import { LoadingState, ErrorState } from '@/components/ui/States';

const pinIcon = L.divIcon({
  className: '',
  html: '<span style="display:block;width:20px;height:20px;border-radius:50%;background:#ea580c;border:3px solid white;box-shadow:0 1px 7px #17255488"></span>',
  iconSize: [20, 20],
  iconAnchor: [10, 10],
});

function FitPins({ pins }) {
  const map = useMap();
  useEffect(() => {
    const observer = new ResizeObserver(() => map.invalidateSize());
    observer.observe(map.getContainer());
    map.invalidateSize();
    return () => observer.disconnect();
  }, [map]);
  useEffect(() => {
    if (pins.length) map.fitBounds(pins.map((pin) => [Number(pin.latitude), Number(pin.longitude)]), { padding: [30, 30], maxZoom: 13 });
  }, [map, pins]);
  return null;
}

export default function ProviderMapPage() {
  const [municipality, setMunicipality] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const { data: barangays = [] } = useQuery({ queryKey: ['public', 'barangays'], queryFn: publicApi.listBarangays });
  const { data: categories = [] } = useQuery({ queryKey: ['public', 'categories'], queryFn: publicApi.listCategories });
  const municipalities = useMemo(() => [...new Set(barangays.map((item) => item.municipality).filter(Boolean))].sort(), [barangays]);
  const params = { ...(municipality ? { municipality } : {}), ...(categoryId ? { category_id: categoryId } : {}) };
  const { data: pins = [], isPending, isError, error, refetch } = useQuery({ queryKey: ['public', 'provider-map', params], queryFn: () => publicApi.providerMap(params) });

  return <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div className="flex flex-wrap items-end justify-between gap-4">
      <div><h1 className="font-display text-2xl font-bold text-navy-800">Provider map</h1><p className="mt-1 text-sm text-ink-muted">Browse verified providers by public service area.</p></div>
      <Link to="/providers" className="text-sm font-semibold text-brand hover:underline">List view</Link>
    </div>
    <div className="my-5 flex flex-wrap gap-3">
      <label className="text-sm text-ink-muted">Municipality <select value={municipality} onChange={(e) => setMunicipality(e.target.value)} className="mt-1 block h-10 rounded-lg border border-line bg-white px-3 text-ink"><option value="">All municipalities</option>{municipalities.map((value) => <option key={value} value={value}>{value}</option>)}</select></label>
      <label className="text-sm text-ink-muted">Category <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} className="mt-1 block h-10 rounded-lg border border-line bg-white px-3 text-ink"><option value="">All categories</option>{categories.map((value) => <option key={value.id} value={value.id}>{value.name}</option>)}</select></label>
    </div>
    {isPending && <LoadingState label="Loading provider areas…" />}
    {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}
    {!isPending && !isError && <>
      <div className="relative z-0 h-[50vh] min-h-80 w-full min-w-0 overflow-hidden rounded-xl border border-line">
        <MapContainer center={[14.3292, 120.8553]} zoom={11} className="h-full w-full">
          <FitPins pins={pins} />
          <TileLayer attribution="&copy; OpenStreetMap contributors" url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
          {pins.map((pin) => <Marker key={`${pin.provider_id}-${pin.barangay}`} position={[Number(pin.latitude), Number(pin.longitude)]} icon={pinIcon}><Popup><strong>{pin.business_name}</strong><br />Serves {pin.barangay}, {pin.municipality}<br /><Link to={`/providers/${pin.provider_id}`}>View profile and services</Link></Popup></Marker>)}
        </MapContainer>
      </div>
      <p className="mt-3 text-xs text-ink-muted">Pins use approximate barangay centers, not home addresses. A pin does not guarantee availability or safety; confirm details with the provider before booking.</p>
      {!pins.length && <p className="mt-3 text-sm text-ink-muted">No eligible providers with configured public service-area coordinates match these filters.</p>}
    </>}
  </div>;
}
