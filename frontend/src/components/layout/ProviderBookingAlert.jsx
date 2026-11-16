import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Modal } from '@/components/ui/Modal';
import { bookingApi } from '@/services/api/bookingApi';
import { queryKeys } from '@/services/api/queryClient';
import { POLL_FAST } from '@/services/realtime/echo';
import { useAuth } from '@/hooks/useAuth';
import { ROLES } from '@/constants';

const STORAGE_KEY = 'serbisyolink:provider-booking-alerts';
const EMPTY_BOOKINGS = Object.freeze([]);

function loadSeenIds() {
  try {
    return new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));
  } catch {
    return new Set();
  }
}

function saveSeenIds(ids) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...ids].slice(-100)));
  } catch {
    // Storage can be unavailable in private browsing; the current tab still works.
  }
}

export function ProviderBookingAlert() {
  const { role } = useAuth();
  const seenIds = useRef(loadSeenIds());
  const [booking, setBooking] = useState(null);
  const { data } = useQuery({
    queryKey: queryKeys.bookings.list({ status: 'pending', per_page: 5 }),
    queryFn: () => bookingApi.list({ status: 'pending', per_page: 5 }),
    enabled: role === ROLES.PROVIDER,
    refetchInterval: POLL_FAST,
  });
  const pendingBookings = data?.items ?? EMPTY_BOOKINGS;

  useEffect(() => {
    if (role !== ROLES.PROVIDER) return;

    if (booking && !pendingBookings.some((item) => item.id === booking.id)) {
      const timer = window.setTimeout(() => setBooking(null), 0);
      return () => window.clearTimeout(timer);
    }

    if (booking) return;

    const next = pendingBookings.find((item) => !seenIds.current.has(item.id));
    if (!next) return;

    seenIds.current.add(next.id);
    saveSeenIds(seenIds.current);
    const timer = window.setTimeout(() => setBooking(next), 0);
    return () => window.clearTimeout(timer);
  }, [booking, pendingBookings, role]);

  useEffect(() => {
    if (!booking) return undefined;
    const timer = window.setTimeout(() => setBooking(null), 5500);
    return () => window.clearTimeout(timer);
  }, [booking]);

  return (
    <Modal
      open={Boolean(booking)}
      onClose={() => setBooking(null)}
      title="New booking request"
      description="A client sent you a booking request. This popup closes automatically after 5 seconds."
      size="sm"
    >
      <div className="space-y-2 text-sm text-ink">
        <p className="font-semibold">{booking?.service?.title ?? 'Service booking'}</p>
        <p>Client: {booking?.client?.full_name ?? 'Client'}</p>
        <p className="text-ink-muted">
          {booking?.scheduled_date} · {booking?.scheduled_start_time?.slice(0, 5)}
        </p>
        <Link
          to={booking ? `/provider/bookings/${booking.id}` : '/provider/bookings'}
          onClick={() => setBooking(null)}
          className="mt-3 inline-flex h-10 items-center rounded-lg bg-navy-700 px-4 text-sm font-semibold text-white hover:bg-navy-600"
        >
          Review booking
        </Link>
      </div>
    </Modal>
  );
}
