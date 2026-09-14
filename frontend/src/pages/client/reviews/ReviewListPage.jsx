import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Textarea } from '@/components/ui/Select';
import { Pagination } from '@/components/ui/Pagination';
import { StarRating } from '@/components/ui/StarRating';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { reviewApi } from '@/services/api/reviewApi';
import { fieldError } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';

export default function ReviewListPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [notice, setNotice] = useState(null);
  const [editing, setEditing] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.reviews.mine({ page }),
    queryFn: () => reviewApi.myReviews({ page }),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['reviews'] });

  const deleteMutation = useMutation({
    mutationFn: reviewApi.remove,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Review deleted. You can write a new one from the booking page.' });
    },
  });

  return (
    <Card title="My Reviews">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

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
              <div className="mt-2 flex gap-2">
                <Button size="sm" variant="subtle" onClick={() => setEditing(review)}>
                  Edit
                </Button>
                <Button size="sm" variant="ghost" className="text-red-600 hover:bg-red-50" onClick={() => setPendingDelete(review)}>
                  Delete
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />

      {editing && (
        <ReviewEditor
          review={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null);
            invalidate();
            setNotice({ tone: 'success', message: 'Review updated.' });
          }}
        />
      )}

      <ConfirmDialog
        open={Boolean(pendingDelete)}
        onClose={() => {
          setPendingDelete(null);
          deleteMutation.reset();
        }}
        onConfirm={() => deleteMutation.mutate(pendingDelete.id)}
        title="Delete this review?"
        description={pendingDelete?.service_title}
        confirmLabel="Delete review"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          It is removed from the provider&apos;s profile and their rating is recalculated. You can write a
          new review for the same booking afterwards.
        </p>
      </ConfirmDialog>
    </Card>
  );
}

function ReviewEditor({ review, onClose, onSaved }) {
  const [rating, setRating] = useState(review.rating);
  const [comment, setComment] = useState(review.comment ?? '');

  const mutation = useMutation({
    mutationFn: () => reviewApi.update(review.id, { rating, comment: comment || null }),
    onSuccess: onSaved,
  });

  const err = mutation.error;

  return (
    <Modal
      open
      onClose={onClose}
      title="Edit review"
      description={review.service_title}
      size="sm"
      footer={
        <>
          <Button variant="subtle" onClick={onClose} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button onClick={() => mutation.mutate()} loading={mutation.isPending}>
            Save changes
          </Button>
        </>
      }
    >
      <div className="grid gap-4">
        {err && !Object.keys(err.errors ?? {}).length && <Alert tone="error">{err.message}</Alert>}
        <div>
          <p className="mb-1.5 text-sm font-medium text-ink">Your rating</p>
          <StarRating value={rating} onChange={setRating} />
          {fieldError(err, 'rating') && <p className="mt-1 text-xs font-medium text-red-600">{fieldError(err, 'rating')}</p>}
        </div>
        <Textarea label="Comment" rows={4} value={comment} onChange={(e) => setComment(e.target.value)} error={fieldError(err, 'comment')} />
      </div>
    </Modal>
  );
}
