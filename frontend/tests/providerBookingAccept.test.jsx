import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import BookingListPage from '@/pages/provider/bookings/BookingListPage';
import { bookingApi } from '@/services/api/bookingApi';
import { renderWithProviders } from './utils';

vi.mock('@/services/api/bookingApi', () => ({
  bookingApi: { list: vi.fn(), accept: vi.fn(), reject: vi.fn() },
}));

describe('provider booking acceptance', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    bookingApi.list.mockResolvedValue({
      items: [{
        id: 42,
        status: 'pending',
        client: { full_name: 'Alex Client' },
        service: { title: 'Plumbing repair' },
        scheduled_date: '2026-10-15',
        scheduled_start_time: '09:00:00',
      }],
      meta: null,
    });
    bookingApi.accept.mockResolvedValue({ id: 42, status: 'accepted' });
  });

  it('requires confirmation before accepting a pending booking', async () => {
    const user = userEvent.setup();
    renderWithProviders(<BookingListPage />);

    await user.click(await screen.findByRole('button', { name: 'Accept', exact: true }));
    expect(bookingApi.accept).not.toHaveBeenCalled();
    expect(screen.getByText('Are you sure you want to accept this booking?')).toBeInTheDocument();
    expect(screen.getByText(/Alex Client · Plumbing repair/)).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Cancel' }));
    expect(bookingApi.accept).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', { name: 'Accept', exact: true }));
    await user.click(screen.getByRole('button', { name: 'Accept Booking' }));
    await waitFor(() => expect(bookingApi.accept).toHaveBeenCalledTimes(1));
    expect(bookingApi.accept.mock.calls[0][0]).toBe(42);
  });
});
