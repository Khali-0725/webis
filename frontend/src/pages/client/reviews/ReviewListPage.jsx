import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Pagination } from '@/components/ui/Pagination';
import { StarRating } from '@/components/ui/StarRating';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { reviewApi } from '@/services/api/reviewApi';
import { queryKeys } from '@/services/api/queryClient';

export default function ReviewListPage() {
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.reviews.mine({ page }),
    queryFn: () => reviewApi.myReviews({ page }),
  });

  return (
    <Card title="My Reviews">
      {isPending && <LoadingState label="Loading reviews…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && data.items.length === 0 && (
        <EmptyState
          title="No reviews yet"
          description="Once a booking is completed, you can rate and review the provider from its details page."
        />
      )}

      {!isPending && !isError && data.items.length > 0 && (
        <ul className="divide-y divide-line">
          {data.items.map((review) => (
            <li key={review.id} className="py-3">
              <div className="flex items-center justify-between gap-3">
                <Link to={`/client/bookings/${review.booking_id}`} className="font-medium text-ink hover:text-brand">
                  {review.service_title}
                </Link>
                <StarRating value={review.rating} size="sm" />
              </div>
              {review.comment && <p className="mt-1 text-sm text-ink-muted">{review.comment}</p>}
              {review.provider_reply && (
                <div className="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-ink-muted">
                  <span className="font-medium text-ink">Provider&apos;s reply: </span>
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
