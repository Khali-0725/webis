import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { StarRating } from '@/components/ui/StarRating';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { reviewApi } from '@/services/api/reviewApi';
import { queryKeys } from '@/services/api/queryClient';

export default function ReviewListPage() {
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.provider.reviews({ page }),
    queryFn: () => reviewApi.providerReviews({ page }),
  });

  return (
    <Card title="Reviews Received">
      {isPending && <LoadingState label="Loading reviews…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && data.items.length === 0 && (
        <EmptyState title="No reviews yet" description="Reviews from your clients will appear here." />
      )}

      {!isPending && !isError && data.items.length > 0 && (
        <ul className="divide-y divide-line">
          {data.items.map((review) => (
            <li key={review.id} className="py-3">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="font-medium text-ink">{review.client?.full_name}</p>
                  <p className="text-sm text-ink-muted">{review.service_title}</p>
                  <Link to={`/provider/bookings/${review.booking_id}`} className="text-xs text-brand hover:underline">
                    Booking {review.booking_reference ?? `#${review.booking_id}`}
                  </Link>
                  {review.scheduled_date && <span className="ml-2 text-xs text-ink-muted">Service date: {review.scheduled_date}</span>}
                </div>
                <div className="flex items-center gap-2">
                  <StarRating value={review.rating} size="sm" />
                  {!review.is_visible && <Badge tone="neutral">Hidden</Badge>}
                </div>
              </div>

              {review.comment && <p className="mt-1 text-sm text-ink-muted">{review.comment}</p>}

              {review.provider_reply && (
                <div className="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-ink-muted">
                  <span className="font-medium text-ink">Your reply: </span>
                  {review.provider_reply}
                </div>
              )}

            </li>
          ))}
        </ul>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />
    </Card>
  );
}
