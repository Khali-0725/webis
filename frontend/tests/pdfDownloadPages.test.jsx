import { beforeEach, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Route, Routes } from 'react-router-dom';
import ReceiptPage from '@/pages/client/payments/ReceiptPage';
import EarningsPage from '@/pages/provider/earnings/EarningsPage';
import { paymentApi } from '@/services/api/paymentApi';
import { earningsApi } from '@/services/api/earningsApi';
import { createReceiptPdf, createEarningsPdf } from '@/utils/pdfDownloads';
import { renderWithProviders } from './utils';

const save = vi.fn();
const receiptBlob = new Blob(['%PDF-1.4'], { type: 'application/pdf' });
vi.mock('@/utils/pdfDownloads', () => ({
  createReceiptPdf: vi.fn(() => ({ output: () => receiptBlob })),
  createEarningsPdf: vi.fn(() => ({ save })),
}));
vi.mock('@/services/api/paymentApi', () => ({ paymentApi: { receipt: vi.fn() } }));
vi.mock('@/services/api/earningsApi', () => ({ earningsApi: {
  summary: vi.fn(), transactions: vi.fn(), overTime: vi.fn(),
} }));
vi.mock('@/components/ui/LineChart', () => ({ LineChart: () => null }));

beforeEach(() => {
  vi.clearAllMocks();
  Object.defineProperty(URL, 'createObjectURL', { configurable: true, value: vi.fn(() => 'blob:webis-receipt') });
  Object.defineProperty(URL, 'revokeObjectURL', { configurable: true, value: vi.fn() });
  vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
});

it('downloads one PDF receipt without a print or text action', async () => {
  paymentApi.receipt.mockResolvedValue({
    receipt_id: 'REC-204', status: 'verified', booking_code: 'BOOK-204',
    service: 'Pipe repair', provider: 'Provider', client: 'Client', amount_due: 800,
  });
  renderWithProviders(
    <Routes><Route path="/client/bookings/:id/receipt" element={<ReceiptPage />} /></Routes>,
    { route: '/client/bookings/204/receipt' },
  );

  const download = await screen.findByRole('button', { name: 'Download PDF' });
  expect(screen.queryByRole('button', { name: /print/i })).not.toBeInTheDocument();
  expect(screen.queryByRole('button', { name: 'Download copy' })).not.toBeInTheDocument();
  await userEvent.setup().click(download);
  expect(createReceiptPdf).toHaveBeenCalledWith(expect.objectContaining({ receipt_id: 'REC-204' }), expect.any(Array), 'Verified Payment');
  expect(URL.createObjectURL).toHaveBeenCalledWith(receiptBlob);
  expect(screen.getByRole('link', { name: 'Open PDF' })).toHaveAttribute('href', 'blob:webis-receipt');
});

it('downloads earnings for the selected date range after transactions load', async () => {
  const summary = { provider_name: 'Sample Provider', total_earnings: 800 };
  const transactions = [{ id: 1, booking_code: 'BOOK-204', service: 'Pipe repair', amount: 800, verified_at: '2026-11-02' }];
  earningsApi.summary.mockResolvedValue(summary);
  earningsApi.transactions.mockResolvedValue(transactions);
  earningsApi.overTime.mockResolvedValue([]);
  renderWithProviders(<EarningsPage />);

  await screen.findByRole('button', { name: 'Download PDF' });
  expect(screen.queryByRole('button', { name: /print/i })).not.toBeInTheDocument();
  await screen.findByText('BOOK-204');
  fireEvent.change(screen.getByLabelText('From'), { target: { value: '2026-09-01' } });
  fireEvent.change(await screen.findByLabelText('To'), { target: { value: '2026-11-04' } });
  await waitFor(() => expect(earningsApi.transactions).toHaveBeenCalledWith({ from: '2026-09-01', to: '2026-11-04' }));
  const filteredDownload = await screen.findByRole('button', { name: 'Download PDF' });
  await waitFor(() => expect(filteredDownload).not.toBeDisabled());
  await userEvent.setup().click(filteredDownload);
  expect(createEarningsPdf).toHaveBeenCalledWith(summary, transactions, '2026-09-01', '2026-11-04');
  expect(save).toHaveBeenCalledExactlyOnceWith('WEBIS-earnings-report.pdf');
});
