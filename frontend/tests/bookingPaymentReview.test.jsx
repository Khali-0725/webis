import { beforeEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes } from 'react-router-dom';
import BookingDetailPage from '@/pages/bookings/BookingDetailPage';
import { bookingApi } from '@/services/api/bookingApi';
import { paymentApi } from '@/services/api/paymentApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/services/api/bookingApi', () => ({ bookingApi: { get: vi.fn() } }));
vi.mock('@/services/api/paymentApi', () => ({
  paymentApi: { getForBooking: vi.fn(), verify: vi.fn(), reject: vi.fn() },
}));
vi.mock('@/services/api/reviewApi', () => ({ reviewApi: { forBooking: vi.fn().mockResolvedValue(null) } }));
vi.mock('@/components/map/LocationView', () => ({ LocationView: () => null }));

function renderBooking(role) {
  setSession(makeUser({ role, home_path: `/${role}/dashboard` }));
  renderWithProviders(
    <Routes><Route path="/:role/bookings/:id" element={<BookingDetailPage />} /></Routes>,
    { route: `/${role}/bookings/42` },
  );
}

describe('booking payment review', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
    bookingApi.get.mockResolvedValue({
      id: 42, booking_code: 'WB-QA', status: 'accepted',
      service: { title: 'QA repair' }, provider: { business_name: 'QA provider' },
      scheduled_date: '2026-10-15', scheduled_start_time: '09:00:00', scheduled_end_time: '10:00:00',
    });
    paymentApi.getForBooking.mockResolvedValue({ id: 88, status: 'proof_submitted', settlement_method: 'online', amount: '500.00' });
    paymentApi.verify.mockResolvedValue({ status: 'verified' });
    paymentApi.reject.mockResolvedValue({ status: 'rejected' });
  });

  it('lets admin verify only after confirming receipt with the provider', async () => {
    const user = userEvent.setup();
    renderBooking('admin');
    await user.click(await screen.findByRole('button', { name: 'Verify payment' }));
    expect(paymentApi.verify).not.toHaveBeenCalled();
    expect(screen.getByText(/Have you confirmed with the provider/)).toBeInTheDocument();
    await user.click(screen.getByRole('button', { name: 'Not yet' }));
    expect(paymentApi.verify).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', { name: 'Verify payment' }));
    await user.click(screen.getByRole('button', { name: 'Yes, receipt confirmed' }));
    await waitFor(() => expect(paymentApi.verify).toHaveBeenCalledExactlyOnceWith(88));
  });

  it('lets admin reject submitted proof with a reason', async () => {
    const user = userEvent.setup();
    renderBooking('admin');
    await user.click(await screen.findByRole('button', { name: 'Reject payment' }));
    expect(screen.getByRole('button', { name: 'Confirm rejection' })).toBeDisabled();
    await user.type(screen.getByPlaceholderText('Reason for rejecting this payment'), 'QA proof does not match provider records.');
    expect(paymentApi.reject).not.toHaveBeenCalled();
    await user.click(screen.getByRole('button', { name: 'Confirm rejection' }));
    await waitFor(() => expect(paymentApi.reject).toHaveBeenCalledExactlyOnceWith(88, 'QA proof does not match provider records.'));
  });

  it('keeps verification and rejection controls away from clients', async () => {
    renderBooking('client');
    await screen.findByText('Proof Submitted');
    expect(screen.queryByRole('button', { name: 'Verify payment' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Reject payment' })).not.toBeInTheDocument();
    expect(paymentApi.verify).not.toHaveBeenCalled();
    expect(paymentApi.reject).not.toHaveBeenCalled();
  });
});
