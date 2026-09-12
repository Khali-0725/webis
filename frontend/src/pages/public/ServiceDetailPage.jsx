import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Alert } from '@/components/ui/Alert';
import { Input } from '@/components/ui/Input';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { LocationPicker } from '@/components/map/LocationPicker';
import { publicApi } from '@/services/api/publicApi';
import { bookingApi } from '@/services/api/bookingApi';
import { conversationApi } from '@/services/api/conversationApi';
import { queryKeys } from '@/services/api/queryClient';
import { useAuth } from '@/hooks/useAuth';
import { PRICING_TYPE_META, ROLES } from '@/constants';

function priceLabelFor(service) {
  return service.pricing_type === 'quote'
    ? 'Quote on request'
    : `₱${service.price}${service.pricing_type === 'hourly' ? '/hr' : ''}`;
}

/**
 * A single wide two-column card: left is what the client is reading
 * (service info, map, notes), right is what they're filling in (schedule,
 * address, submit). Same fields/handlers as before - only the arrangement
 * changed, to use the desktop width instead of a squeezed sidebar column.
 */
function BookingRequestForm({ service, onClose }) {
  const navigate = useNavigate();
  const providerId = service.provider?.id;

  const [date, setDate] = useState('');
  const [startTime, setStartTime] = useState('');
  const [barangayId, setBarangayId] = useState('');
  const [addressLine, setAddressLine] = useState('');
  const [pin, setPin] = useState({ latitude: null, longitude: null });
  const [landmarkNotes, setLandmarkNotes] = useState('');
  const [clientNotes, setClientNotes] = useState('');
  const [formError, setFormError] = useState(null);

  const { data: barangays = [] } = useQuery({
    queryKey: queryKeys.public.barangays,
    queryFn: publicApi.listBarangays,
  });

  const slotsQuery = useQuery({
    queryKey: queryKeys.bookings.availability(providerId, date),
    queryFn: () => bookingApi.getAvailability(providerId, date),
    enabled: Boolean(providerId && date),
  });

  const createMutation = useMutation({
    mutationFn: bookingApi.create,
    onSuccess: (booking) => navigate('/client/bookings', { state: { bookingCreated: booking.booking_code } }),
    onError: (error) => setFormError(error?.message ?? 'Failed to create the booking.'),
  });

  const minDate = new Date().toISOString().slice(0, 10);
  const hasPin = pin.latitude != null && pin.longitude != null;

  const handleSubmit = (event) => {
    event.preventDefault();
    setFormError(null);

    if (!startTime) {
      setFormError('Please select a time slot.');
      return;
    }
    if (!hasPin) {
      setFormError('Please drop a pin on the map for the service location.');
      return;
    }

    createMutation.mutate({
      service_id: service.id,
      scheduled_date: date,
      scheduled_start_time: startTime,
      barangay_id: Number(barangayId),
      address_line: addressLine,
      latitude: pin.latitude,
      longitude: pin.longitude,
      landmark_notes: landmarkNotes || undefined,
      client_notes: clientNotes || undefined,
    });
  };

  const provider = service.provider;

  return (
    <form onSubmit={handleSubmit} className="webis-card mx-auto max-w-4xl p-6 sm:p-8">
      {formError && (
        <Alert tone="error" className="mb-6">
          {formError}
        </Alert>
      )}

      <div className="grid gap-8 md:grid-cols-[1fr_320px]">
        {/* Left: what the client is reading - service, price, map, notes. */}
        <div className="space-y-5">
          <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-brand">
              {service.category?.name}
            </p>
            <h1 className="font-display text-2xl font-bold text-navy-800">{service.title}</h1>
          </div>

          <div>
            <h2 className="text-sm font-semibold text-ink">Description</h2>
            <p className="mt-1 whitespace-pre-line text-sm text-ink-muted">{service.description}</p>
          </div>

          <div className="flex divide-x divide-line rounded-lg border border-line">
            <div className="flex-1 px-4 py-3">
              <p className="text-xs text-ink-muted">Pricing</p>
              <p className="font-display text-xl font-bold text-navy-800">{priceLabelFor(service)}</p>
              <p className="text-xs text-ink-muted">{PRICING_TYPE_META[service.pricing_type]?.label}</p>
            </div>
            <div className="flex-1 px-4 py-3">
              <p className="text-xs text-ink-muted">Provider</p>
              <p className="font-semibold text-navy-800">{provider?.business_name}</p>
              {provider?.base_barangay && (
                <p className="text-xs text-ink-muted">{provider.base_barangay.name}</p>
              )}
            </div>
          </div>

          <div>
            <h2 className="mb-2 text-sm font-semibold text-ink">Service location</h2>
            <LocationPicker
              latitude={pin.latitude}
              longitude={pin.longitude}
              onChange={(lat, lng) => setPin({ latitude: lat, longitude: lng })}
              height={320}
            />
          </div>

          <div>
            <label htmlFor="landmark-notes" className="mb-1.5 block text-sm font-medium text-ink">
              Landmark notes
            </label>
            <textarea
              id="landmark-notes"
              rows={2}
              value={landmarkNotes}
              onChange={(event) => setLandmarkNotes(event.target.value)}
              className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
              placeholder="e.g. Near the sari-sari store, blue gate"
            />
          </div>

          <div>
            <label htmlFor="client-notes" className="mb-1.5 block text-sm font-medium text-ink">
              Notes for the provider
            </label>
            <textarea
              id="client-notes"
              rows={2}
              value={clientNotes}
              onChange={(event) => setClientNotes(event.target.value)}
              className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
            />
          </div>
        </div>

        {/* Right: what the client is filling in - schedule, address, submit. */}
        <div className="space-y-5">
          <div>
            <label htmlFor="booking-date" className="mb-1.5 block text-sm font-medium text-ink">
              Date
            </label>
            <input
              id="booking-date"
              type="date"
              required
              min={minDate}
              value={date}
              onChange={(event) => {
                setDate(event.target.value);
                setStartTime('');
              }}
              className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            />
          </div>

          {date && (
            <div>
              <p className="mb-1.5 text-sm font-medium text-ink">Available time slots</p>
              {slotsQuery.isPending && <p className="text-xs text-ink-muted">Loading slots…</p>}
              {slotsQuery.isSuccess && slotsQuery.data.length === 0 && (
                <p className="text-xs text-ink-muted">No free slots on this date. Try another day.</p>
              )}
              <div className="flex flex-wrap gap-2">
                {slotsQuery.data?.map((slot) => (
                  <button
                    key={slot.start_time}
                    type="button"
                    onClick={() => setStartTime(slot.start_time)}
                    className={`rounded-lg border px-3 py-1.5 text-sm ${
                      startTime === slot.start_time
                        ? 'border-navy-700 bg-navy-700 text-white'
                        : 'border-line text-ink hover:border-navy-500'
                    }`}
                  >
                    {slot.start_time}
                  </button>
                ))}
              </div>
            </div>
          )}

          <div>
            <label htmlFor="booking-barangay" className="mb-1.5 block text-sm font-medium text-ink">
              Barangay<span className="ml-0.5 text-red-600">*</span>
            </label>
            <select
              id="booking-barangay"
              required
              value={barangayId}
              onChange={(event) => setBarangayId(event.target.value)}
              className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
            >
              <option value="">Select a barangay</option>
              {barangays.map((barangay) => (
                <option key={barangay.id} value={barangay.id}>
                  {barangay.name}
                </option>
              ))}
            </select>
          </div>

          <Input
            label="Address"
            required
            value={addressLine}
            onChange={(event) => setAddressLine(event.target.value)}
            placeholder="House no., street"
          />

          <div>
            <p className="text-sm font-medium text-ink">
              Pin the exact location<span className="ml-0.5 text-red-600">*</span>
            </p>
            <p className="mt-0.5 text-xs text-ink-muted">
              {hasPin
                ? 'Only you and the provider (once they accept) can see this pin.'
                : 'Drop a pin on the map to the left.'}
            </p>
          </div>

          <div className="space-y-2 pt-2">
            <Button type="submit" fullWidth loading={createMutation.isPending}>
              Send booking request
            </Button>
            <Button type="button" fullWidth variant="outline" onClick={onClose}>
              Cancel
            </Button>
          </div>
        </div>
      </div>
    </form>
  );
}

export default function ServiceDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { isAuthenticated, role } = useAuth();
  const [showForm, setShowForm] = useState(false);
  const [messageError, setMessageError] = useState(null);

  const startConversation = useMutation({
    mutationFn: (providerProfileId) => conversationApi.start({ provider_profile_id: providerProfileId }),
    onSuccess: (conversation) => navigate(`/client/messages/${conversation.id}`),
    onError: (error) => setMessageError(error?.message ?? 'Failed to start the conversation.'),
  });

  const {
    data: service,
    isPending,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: queryKeys.public.service(id),
    queryFn: () => publicApi.getService(id),
  });

  if (isPending) {
    return (
      <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <LoadingState label="Loading service…" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <ErrorState description={error?.message} onRetry={() => refetch()} />
      </div>
    );
  }

  const provider = service.provider;
  const canBook = isAuthenticated && role === ROLES.CLIENT;

  if (canBook && showForm) {
    return (
      <div className="px-4 py-10 sm:px-6">
        <BookingRequestForm service={service} onClose={() => setShowForm(false)} />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6">
      <p className="text-xs font-medium uppercase tracking-wide text-brand">
        {service.category?.name}
      </p>
      <h1 className="mt-1 font-display text-3xl font-bold text-navy-800">{service.title}</h1>

      <div className="mt-6 grid gap-8 sm:grid-cols-[1fr_300px]">
        <div>
          <h2 className="font-display text-lg font-semibold text-navy-800">Description</h2>
          <p className="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink-muted">
            {service.description}
          </p>
        </div>

        <aside className="webis-card space-y-4 p-5">
          <div>
            <p className="text-xs text-ink-muted">Pricing</p>
            <p className="font-display text-2xl font-bold text-navy-800">{priceLabelFor(service)}</p>
            <p className="text-xs text-ink-muted">{PRICING_TYPE_META[service.pricing_type]?.label}</p>
          </div>

          <div className="border-t border-line pt-4">
            <p className="text-xs text-ink-muted">Provider</p>
            <Link
              to={`/providers/${provider?.id ?? ''}`}
              className="mt-1 block font-semibold text-navy-800 hover:text-brand"
            >
              {provider?.business_name}
            </Link>
            {provider?.base_barangay && (
              <p className="mt-1 text-xs text-ink-muted">{provider.base_barangay.name}</p>
            )}
            {provider?.rating_count > 0 && (
              <Badge tone="success" className="mt-2">
                ★ {provider.rating_avg} ({provider.rating_count} reviews)
              </Badge>
            )}
          </div>

          {messageError && <Alert tone="error">{messageError}</Alert>}

          <div className="space-y-2 border-t border-line pt-4">
            {isAuthenticated && role === ROLES.CLIENT ? (
              <Button
                fullWidth
                variant="outline"
                onClick={() => startConversation.mutate(provider?.id)}
                loading={startConversation.isPending}
              >
                Message provider
              </Button>
            ) : (
              <Button
                fullWidth
                variant="outline"
                disabled
                title={isAuthenticated ? 'Only client accounts can message providers.' : 'Sign in to message this provider.'}
              >
                Message provider
              </Button>
            )}

            {!isAuthenticated && (
              <Link to="/login" className="block">
                <Button fullWidth>Sign in to book</Button>
              </Link>
            )}

            {isAuthenticated && !canBook && (
              <Button fullWidth disabled title="Only client accounts can book services.">
                Book this service
              </Button>
            )}

            {canBook && (
              <Button fullWidth onClick={() => setShowForm(true)}>
                Book this service
              </Button>
            )}
          </div>
        </aside>
      </div>
    </div>
  );
}
