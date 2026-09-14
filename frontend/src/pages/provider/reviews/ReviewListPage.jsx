import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { StarRating } from '@/components/ui/StarRating';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { reviewApi } from '@/services/api/reviewApi';
import { queryKeys } from '@/services/api/queryClient';

export default function ReviewListPage() {
  const queryClient = useQueryClient();
  const [replyDrafts, setReplyDrafts] = useState({});
  const [openReplyFor, setOpenReplyFor] = useState(null);
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.provider.reviews({ page }),
    queryFn: () => reviewApi.providerReviews({ page }),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['provider', 'reviews'] });

  const replyMutation = useMutation({
    mutationFn: ({ id, text }) => reviewApi.reply(id, text),
    onSuccess: (_, { id }) => {
      invalidate();
      setOpenReplyFor(null);
      setReplyDrafts((current) => ({ ...current, [id]: '' }));
    },
  });

  const visibilityMutation = useMutation({
    mutationFn: ({ id, isVisible }) => reviewApi.setVisibility(id, isVisible),
    onSuccess: invalidate,
  });

  const removeReplyMutation = useMutation({
    mutationFn: (id) => reviewApi.removeReply(id),
    onSuccess: invalidate,
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
                  <Link to={`/provider/bookings/${review.booking_id}`} className="font-medium text-ink hover:text-brand">
                    {review.client?.full_name}
                  </Link>
                  <span className="ml-2 text-xs text-ink-muted">{review.service_title}</span>
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

              <div className="mt-2 flex flex-wrap gap-2">
                {!review.provider_reply && openReplyFor !== review.id && (
                  <Button size="sm" variant="outline" onClick={() => setOpenReplyFor(review.id)}>
                    Reply
                  </Button>
                )}
                {review.provider_reply && openReplyFor !== review.id && (
                  <>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => {
                        setReplyDrafts((current) => ({ ...current, [review.id]: review.provider_reply }));
                        setOpenReplyFor(review.id);
                      }}
                    >
                      Edit reply
                    </Button>
                    <Button
                      size="sm"
                      variant="subtle"
                      loading={removeReplyMutation.isPending && removeReplyMutation.variables === review.id}
                      onClick={() => removeReplyMutation.mutate(review.id)}
                    >
                      Remove reply
                    </Button>
                  </>
                )}
                <Button
                  size="sm"
                  variant="subtle"
                  loading={visibilityMutation.isPending && visibilityMutation.variables?.id === review.id}
                  onClick={() => visibilityMutation.mutate({ id: review.id, isVisible: !review.is_visible })}
                >
                  {review.is_visible ? 'Hide' : 'Unhide'}
                </Button>
              </div>

              {openReplyFor === review.id && (
                <div className="mt-2 space-y-2">
                  <textarea
                    rows={2}
                    value={replyDrafts[review.id] ?? ''}
                    onChange={(event) =>
                      setReplyDrafts((current) => ({ ...current, [review.id]: event.target.value }))
                    }
                    placeholder="Write a reply"
                    className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
                  />
                  <Button
                    size="sm"
                    loading={replyMutation.isPending}
                    onClick={() => replyMutation.mutate({ id: review.id, text: replyDrafts[review.id] })}
                    disabled={!replyDrafts[review.id]}
                  >
                    Post reply
                  </Button>
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
