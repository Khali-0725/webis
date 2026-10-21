import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card, StatCard } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { LineChart } from '@/components/ui/LineChart';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { earningsApi } from '@/services/api/earningsApi';

const peso = (value) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function EarningsPage() {
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const params = { ...(from ? { from } : {}), ...(to ? { to } : {}) };
  const summaryQuery = useQuery({ queryKey: ['provider', 'earnings', 'summary', params], queryFn: () => earningsApi.summary(params) });
  const transactionsQuery = useQuery({ queryKey: ['provider', 'earnings', 'transactions', params], queryFn: () => earningsApi.transactions(params) });
  const overTimeQuery = useQuery({ queryKey: ['provider', 'earnings', 'over-time'], queryFn: earningsApi.overTime });

  if (summaryQuery.isPending) return <LoadingState label="Loading earnings…" />;
  if (summaryQuery.isError) return <ErrorState description={summaryQuery.error?.message} onRetry={() => summaryQuery.refetch()} />;
  const summary = summaryQuery.data;

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-end gap-3 print:hidden">
        <label className="text-sm text-ink-muted">From <input type="date" value={from} max={to || undefined} onChange={(e) => setFrom(e.target.value)} className="mt-1 block h-10 rounded-lg border border-line bg-white px-2 text-ink" /></label>
        <label className="text-sm text-ink-muted">To <input type="date" value={to} min={from || undefined} onChange={(e) => setTo(e.target.value)} className="mt-1 block h-10 rounded-lg border border-line bg-white px-2 text-ink" /></label>
        <Button variant="outline" onClick={() => window.print()}>Print Earnings Report</Button>
      </div>
      <div className="hidden print:block">
        <h2 className="font-display text-2xl font-bold text-navy-800">WEBIS · Earnings Report</h2>
        <p>{summary.provider_name} · {from || 'All dates'} to {to || 'Present'}</p>
        <p>Printed {new Date().toLocaleString('en-PH')}</p>
      </div>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 print:hidden">
        <StatCard value={peso(summary.total_earnings)} label="Confirmed Earnings" tone="success" />
        <StatCard value={peso(summary.pending_amount)} label="Pending / Unverified" tone="warning" />
        <StatCard value={peso(summary.rejected_amount)} label="Rejected" />
        <StatCard value={peso(summary.refunded_amount)} label="Refunded" />
      </div>
      <p className="hidden print:block">Confirmed earned amount: {peso(summary.total_earnings)}</p>
      <Card title="Confirmed transactions">
        {transactionsQuery.isPending && <LoadingState label="Loading transactions…" />}
        {transactionsQuery.isError && <ErrorState description={transactionsQuery.error?.message} onRetry={() => transactionsQuery.refetch()} />}
        {transactionsQuery.data && (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead><tr className="border-b border-line"><th className="py-2">Booking</th><th>Service</th><th>Verified</th><th className="text-right">Amount</th></tr></thead>
              <tbody>{transactionsQuery.data.map((row) => <tr key={row.id} className="border-b border-line"><td className="py-2">{row.booking_code}</td><td>{row.service}</td><td>{new Date(row.verified_at).toLocaleDateString('en-PH')}</td><td className="text-right">{peso(row.amount)}</td></tr>)}</tbody>
            </table>
          </div>
        )}
      </Card>
      <div className="print:hidden">
        <Card title="Earnings Over Time (last 30 days)">
          {overTimeQuery.isPending && <LoadingState label="Loading…" />}
          {!overTimeQuery.isPending && !overTimeQuery.isError && <LineChart points={overTimeQuery.data} valueKey="total" color="#0f766e" formatValue={peso} />}
          {overTimeQuery.isError && <ErrorState description={overTimeQuery.error?.message} onRetry={() => overTimeQuery.refetch()} />}
        </Card>
      </div>
    </div>
  );
}
