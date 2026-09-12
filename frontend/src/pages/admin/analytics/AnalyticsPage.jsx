import { useQuery } from '@tanstack/react-query';
import { Card, StatCard } from '@/components/ui/Card';
import { LineChart } from '@/components/ui/LineChart';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { analyticsApi } from '@/services/api/admin/analyticsApi';
import { queryKeys } from '@/services/api/queryClient';

function formatPeso(value) {
  return `₱${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

function BarRow({ label, value, max }) {
  return (
    <div className="flex items-center gap-2 text-xs text-ink-muted">
      <span className="w-32 shrink-0 truncate">{label}</span>
      <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
        <div
          className="h-full rounded-full bg-brand"
          style={{ width: max > 0 ? `${(value / max) * 100}%` : '0%' }}
        />
      </div>
      <span className="w-10 shrink-0 text-right">{value}</span>
    </div>
  );
}

export default function AnalyticsPage() {
  const summaryQuery = useQuery({ queryKey: queryKeys.admin.analyticsSummary, queryFn: analyticsApi.summary });
  const bookingsOverTimeQuery = useQuery({
    queryKey: queryKeys.admin.analyticsBookingsOverTime,
    queryFn: analyticsApi.bookingsOverTime,
  });
  const earningsOverTimeQuery = useQuery({
    queryKey: queryKeys.admin.analyticsEarningsOverTime,
    queryFn: analyticsApi.earningsOverTime,
  });
  const categoryQuery = useQuery({
    queryKey: queryKeys.admin.analyticsBookingsByCategory,
    queryFn: analyticsApi.bookingsByCategory,
  });
  const topProvidersQuery = useQuery({
    queryKey: queryKeys.admin.analyticsTopProviders,
    queryFn: analyticsApi.topProviders,
  });
  const paymentQuery = useQuery({
    queryKey: queryKeys.admin.analyticsPaymentSummary,
    queryFn: analyticsApi.paymentSummary,
  });

  if (summaryQuery.isPending) return <LoadingState label="Loading analytics…" />;
  if (summaryQuery.isError) {
    return <ErrorState description={summaryQuery.error?.message} onRetry={() => summaryQuery.refetch()} />;
  }

  const s = summaryQuery.data;
  const categories = categoryQuery.data ?? [];
  const maxCategoryTotal = Math.max(1, ...categories.map((c) => Number(c.total)));
  const payments = paymentQuery.data ?? [];

  return (
    <div className="space-y-5">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard value={s.users.total} label="Total Users" tone="navy" />
        <StatCard value={s.providers.verified} label="Verified Providers" tone="success" />
        <StatCard value={s.bookings.total} label="Total Bookings" tone="brand" />
        <StatCard value={s.bookings.disputed} label="Disputed Bookings" tone="warning" />
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard value={s.users.clients} label="Clients" />
        <StatCard value={s.users.providers} label="Providers" />
        <StatCard value={s.bookings.completed} label="Completed Bookings" tone="success" />
        <StatCard value={formatPeso(s.payments.total_earnings)} label="Total Earnings" tone="success" />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card title="Bookings Over Time (last 30 days)">
          {bookingsOverTimeQuery.isPending && <LoadingState label="Loading…" />}
          {!bookingsOverTimeQuery.isPending && (
            <LineChart points={bookingsOverTimeQuery.data} valueKey="total" color="#1d4ed8" />
          )}
        </Card>

        <Card title="Earnings Over Time (last 30 days)">
          {earningsOverTimeQuery.isPending && <LoadingState label="Loading…" />}
          {!earningsOverTimeQuery.isPending && (
            <LineChart
              points={earningsOverTimeQuery.data}
              valueKey="total"
              color="#0f766e"
              formatValue={formatPeso}
            />
          )}
        </Card>
      </div>

      <Card title="Bookings by Category">
        {categoryQuery.isPending && <LoadingState label="Loading…" />}
        {!categoryQuery.isPending && categories.length === 0 && (
          <p className="text-sm text-ink-muted">No bookings yet.</p>
        )}
        {categories.length > 0 && (
          <div className="space-y-2">
            {categories.map((c) => (
              <BarRow key={c.category} label={c.category} value={Number(c.total)} max={maxCategoryTotal} />
            ))}
          </div>
        )}
      </Card>

      <Card title="Top Providers (by rating, min. 3 reviews)">
        {topProvidersQuery.isPending && <LoadingState label="Loading…" />}
        {!topProvidersQuery.isPending && (topProvidersQuery.data?.by_rating?.length ?? 0) === 0 && (
          <p className="text-sm text-ink-muted">No providers meet the minimum review threshold yet.</p>
        )}
        {topProvidersQuery.data?.by_rating?.length > 0 && (
          <ul className="divide-y divide-line">
            {topProvidersQuery.data.by_rating.map((p) => (
              <li key={p.id} className="flex items-center justify-between py-2 text-sm">
                <span className="text-ink">{p.business_name}</span>
                <span className="text-ink-muted">
                  {p.rating_avg} ★ ({p.rating_count})
                </span>
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Card title="Payment Summary">
        {paymentQuery.isPending && <LoadingState label="Loading…" />}
        {!paymentQuery.isPending && payments.length === 0 && (
          <p className="text-sm text-ink-muted">No payments yet.</p>
        )}
        {payments.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                  <th className="py-2 pr-4 font-medium">Status</th>
                  <th className="py-2 pr-4 font-medium">Count</th>
                  <th className="py-2 font-medium">Total Amount</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {payments.map((p) => (
                  <tr key={p.status}>
                    <td className="py-2 pr-4 text-ink">{p.status}</td>
                    <td className="py-2 pr-4 text-ink-muted">{p.total}</td>
                    <td className="py-2 text-ink-muted">{p.amount_sum}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}
