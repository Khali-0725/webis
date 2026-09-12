import { useQuery } from '@tanstack/react-query';
import { Card, StatCard } from '@/components/ui/Card';
import { LineChart } from '@/components/ui/LineChart';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { earningsApi } from '@/services/api/earningsApi';
import { queryKeys } from '@/services/api/queryClient';

function formatPeso(value) {
  return `₱${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export default function EarningsPage() {
  const summaryQuery = useQuery({
    queryKey: queryKeys.provider.earningsSummary,
    queryFn: earningsApi.summary,
  });
  const overTimeQuery = useQuery({
    queryKey: queryKeys.provider.earningsOverTime,
    queryFn: earningsApi.overTime,
  });

  if (summaryQuery.isPending) return <LoadingState label="Loading earnings…" />;
  if (summaryQuery.isError) {
    return <ErrorState description={summaryQuery.error?.message} onRetry={() => summaryQuery.refetch()} />;
  }

  const s = summaryQuery.data;

  return (
    <div className="space-y-5">
      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard value={formatPeso(s.total_earnings)} label="Total Earnings" tone="success" />
        <StatCard value={s.verified_count} label="Verified Payments" tone="brand" />
        <StatCard value={s.awaiting_review_count} label="Awaiting Your Review" tone="warning" />
      </div>

      <Card title="Earnings Over Time (last 30 days)">
        {overTimeQuery.isPending && <LoadingState label="Loading…" />}
        {!overTimeQuery.isPending && !overTimeQuery.isError && (
          <LineChart points={overTimeQuery.data} valueKey="total" color="#0f766e" formatValue={formatPeso} />
        )}
        {overTimeQuery.isError && (
          <ErrorState description={overTimeQuery.error?.message} onRetry={() => overTimeQuery.refetch()} />
        )}
      </Card>
    </div>
  );
}
