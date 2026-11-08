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

  it('sends client name search to the API and resets pagination', async () => {
    bookingApi.list.mockResolvedValue({ items: [], meta: { page: 1, last_page: 3 } });
    const user = userEvent.setup();
    renderWithProviders(<BookingListPage />);
    await user.click(await screen.findByRole('button', { name: 'Next' }));
    await waitFor(() => expect(bookingApi.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 })));
    await user.type(screen.getByRole('textbox', { name: 'Search bookings' }), 'Ethan Hayes');
    await waitFor(() => expect(bookingApi.list).toHaveBeenLastCalledWith(expect.objectContaining({ page: 1, search: 'Ethan Hayes' })));
  });

  it('requires confirmation before accepting a pending booking', async () => {
    const user = userEvent.setup();
    renderWithProviders(<BookingListPage />);

    const accept = await screen.findByRole('button', { name: 'Accept booking 42' });
    expect(accept.textContent).toBe('');
    await user.click(accept);
    expect(bookingApi.accept).not.toHaveBeenCalled();
    expect(screen.getByText('Are you sure you want to accept this booking?')).toBeInTheDocument();
    expect(screen.getByText(/Alex Client · Plumbing repair/)).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Cancel' }));
    expect(bookingApi.accept).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', { name: 'Accept booking 42' }));
    await user.click(screen.getByRole('button', { name: 'Accept Booking' }));
    await waitFor(() => expect(bookingApi.accept).toHaveBeenCalledTimes(1));
    expect(bookingApi.accept.mock.calls[0][0]).toBe(42);
  });
});
