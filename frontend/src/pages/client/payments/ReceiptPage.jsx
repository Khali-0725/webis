import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { paymentApi } from '@/services/api/paymentApi';
import { createReceiptPdf } from '@/utils/pdfDownloads';

const display = (value) => value || '—';
const money = (value) => value == null ? '—' : `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const date = (value) => value ? new Date(value).toLocaleString('en-PH', { timeZone: 'Asia/Manila' }) : '—';

export default function ReceiptPage() {
  const { id } = useParams();
  const { data: receipt, isPending, isError, error, refetch } = useQuery({
    queryKey: ['payment-receipt', id],
    queryFn: () => paymentApi.receipt(id),
  });

  if (isPending) return <LoadingState label="Loading receipt…" />;
  if (isError) return <ErrorState description={error?.message} onRetry={() => refetch()} />;

  const designation = receipt.status === 'verified'
    ? 'Verified Payment'
    : receipt.status === 'proof_submitted'
      ? 'Payment Submitted — Not Yet Verified'
      : receipt.status === 'rejected'
        ? 'Payment Proof Rejected'
        : 'Payment Not Yet Verified';
  const fields = [
    ['Receipt identifier', receipt.receipt_id],
    ['Booking reference', receipt.booking_code],
    ['Service', receipt.service],
    ['Provider', receipt.provider],
    ['Client', receipt.client],
    ['Amount due', money(receipt.amount_due)],
    ['Amount declared paid', money(receipt.amount_declared_paid)],
    ['Payment reference', receipt.reference_number],
    ['Payment date', date(receipt.payment_date)],
    ['Submitted at', date(receipt.submitted_at)],
    ['Payment method', receipt.payment_method],
    ['Verified at', date(receipt.verified_at)],
  ];

  const download = () => {
    const filename = String(receipt.receipt_id || 'WEBIS-receipt').replace(/[^a-zA-Z0-9_-]/g, '-');
    createReceiptPdf(receipt, fields, designation).save(`${filename}.pdf`);
  };

  return (
    <div className="mx-auto max-w-2xl">
      <div className="mb-4 flex flex-wrap gap-2">
        <Link to={`/client/bookings/${id}`}><Button variant="outline">Back to booking</Button></Link>
        <Button onClick={download}>Download PDF</Button>
      </div>
      <Card>
        <div className="border-b border-line pb-5">
          <p className="font-display text-2xl font-bold text-navy-800">WEBIS</p>
          <p className="text-sm text-ink-muted">Booking payment record</p>
          <h1 className="mt-4 text-xl font-semibold text-ink">{designation}</h1>
          {receipt.status !== 'verified' && <p className="mt-1 text-sm text-ink-muted">This record does not confirm that funds were received.</p>}
        </div>
        <dl className="divide-y divide-line text-sm">
          {fields.map(([label, value]) => (
            <div key={label} className="grid grid-cols-2 gap-3 py-3">
              <dt className="text-ink-muted">{label}</dt><dd className="break-words font-medium text-ink">{display(value)}</dd>
            </div>
          ))}
        </dl>
        <p className="mt-5 text-xs text-ink-muted">Generated {date(new Date().toISOString())} · WEBIS, Tanza, Cavite</p>
      </Card>
    </div>
  );
}
