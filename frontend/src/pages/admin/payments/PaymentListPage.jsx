import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { paymentApi } from '@/services/api/paymentApi';

const STATUS_TONE = {
  pending: 'neutral',
  proof_submitted: 'warning',
  verified: 'success',
  rejected: 'danger',
  refunded: 'info',
};

export default function AdminPaymentListPage() {
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['admin', 'payments', page],
    queryFn: () => paymentApi.list({ page }),
  });

  const payments = data?.items ?? [];

  return (
    <Card title="All Payments">
      {isPending && <LoadingState label="Loading payments…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && payments.length === 0 && (
        <EmptyState title="No payments yet" description="Payments will appear here once bookings are made." />
      )}

      {!isPending && !isError && payments.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Booking</th>
                <th className="py-2 pr-4 font-medium">Amount</th>
                <th className="py-2 pr-4 font-medium">Reference</th>
                <th className="py-2 font-medium">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {payments.map((p) => (
                <tr key={p.id}>
                  <td className="py-3 pr-4 text-ink">#{p.booking_id}</td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {p.currency} {p.amount}
                  </td>
                  <td className="py-3 pr-4 text-ink-muted">{p.reference_number ?? '—'}</td>
                  <td className="py-3">
                    <Badge tone={STATUS_TONE[p.status] ?? 'neutral'}>{p.status_label}</Badge>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />
    </Card>
  );
}
