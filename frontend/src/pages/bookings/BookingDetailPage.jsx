import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Badge, StatusBadge } from '@/components/ui/Badge';
import { StarRating } from '@/components/ui/StarRating';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { LocationView } from '@/components/map/LocationView';
import { bookingApi } from '@/services/api/bookingApi';
import { conversationApi } from '@/services/api/conversationApi';
import { paymentApi } from '@/services/api/paymentApi';
import { reportApi } from '@/services/api/reportApi';
import { reviewApi } from '@/services/api/reviewApi';
import { queryKeys } from '@/services/api/queryClient';
import { useAuth } from '@/hooks/useAuth';
import { BOOKING_TRANSITIONS, PAYMENT_STATUS_META, REPORT_REASON_META, ROLES, SETTLEMENT_METHOD_META } from '@/constants';

/**
 * Manual QR-proof settlement (Phase 7) - one section, both audiences: the
 * client sees the provider's QR once accepted and can upload proof, the
 * provider sees the submitted proof and verifies/rejects it.
 */
function PaymentSection({ booking, isClient, isProvider }) {
  const queryClient = useQueryClient();
  const [referenceNumber, setReferenceNumber] = useState('');
  const [proofFile, setProofFile] = useState(null);
  const [rejectReason, setRejectReason] = useState('');
  const [showRejectForm, setShowRejectForm] = useState(false);
  const [showVerifyConfirm, setShowVerifyConfirm] = useState(false);
  const [showReportForm, setShowReportForm] = useState(false);
  const [reportReason, setReportReason] = useState('non_payment');
  const [reportDetails, setReportDetails] = useState('');
  const [reportSubmitted, setReportSubmitted] = useState(false);
  const [notice, setNotice] = useState(null);

  const { data: payment, isPending } = useQuery({
    queryKey: queryKeys.payments.forBooking(booking.id),
    queryFn: () => paymentApi.getForBooking(booking.id),
    refetchInterval: 5_000,
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.payments.forBooking(booking.id) });

  const submitProofMutation = useMutation({
    mutationFn: () => paymentApi.submitProof(booking.id, { proof: proofFile, reference_number: referenceNumber }),
    onSuccess: () => {
      invalidate();
      setProofFile(null);
      setNotice({ tone: 'success', message: 'Payment proof submitted. Waiting for the provider to verify it.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to submit payment proof.' }),
  });

  const verifyMutation = useMutation({
    mutationFn: () => paymentApi.verify(payment.id),
    onSuccess: () => {
      invalidate();
      setShowVerifyConfirm(false);
      setNotice({ tone: 'success', message: 'Payment marked as received. You can now mark this job completed.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to verify the payment.' }),
  });

  const rejectMutation = useMutation({
    mutationFn: () => paymentApi.reject(payment.id, rejectReason),
    onSuccess: () => {
      invalidate();
      setShowRejectForm(false);
      setRejectReason('');
      setNotice({ tone: 'success', message: 'Payment rejected.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to reject the payment.' }),
  });

  const reportMutation = useMutation({
    mutationFn: () =>
      reportApi.submit({
        reportable_type: 'booking',
        reportable_id: booking.id,
        reason: reportReason,
        details: reportDetails || undefined,
      }),
    onSuccess: () => {
      setShowReportForm(false);
      setReportSubmitted(true);
      setNotice({ tone: 'success', message: 'Report submitted. Our admin team will review this booking.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to submit the report.' }),
  });

  if (isPending || !payment) {
    return (
      <Card title="Payment">
        <LoadingState label="Loading payment…" />
      </Card>
    );
  }

  const meta = PAYMENT_STATUS_META[payment.status] ?? { label: payment.status_label, tone: 'neutral' };
  const settlementMeta = SETTLEMENT_METHOD_META[payment.settlement_method];
  const isCash = payment.settlement_method === 'cash';

  // Cash has no proof to submit - the provider confirms straight from
  // Pending. Online still requires the client's proof first.
  const canConfirmReceipt = isProvider && (
    (isCash && payment.status === 'pending')
    || (!isCash && payment.status === 'proof_submitted')
  );
  const canReject = isProvider && !isCash && payment.status === 'proof_submitted';

  const handleSubmitProof = (event) => {
    event.preventDefault();
    setNotice(null);
    if (!proofFile) {
      setNotice({ tone: 'error', message: 'Please attach a screenshot of your payment.' });
      return;
    }
    submitProofMutation.mutate();
  };

  return (
    <Card title="Payment" action={<Badge tone={meta.tone}>{meta.label}</Badge>}>
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      <div className="grid gap-6 md:grid-cols-2">
        <div>
          <p className="text-xs text-ink-muted">Amount</p>
          <p className="font-display text-lg font-bold text-navy-800">
            {payment.amount != null ? `₱${payment.amount}` : 'To be confirmed'}
          </p>

          {payment.status === 'rejected' && payment.rejection_reason && (
            <p className="mt-2 text-sm text-red-600">Rejected: {payment.rejection_reason}</p>
          )}

          {payment.current_proof && (
            <div className="mt-3">
              <p className="text-xs text-ink-muted">Submitted proof</p>
              <a
                href={payment.current_proof.url}
                target="_blank"
                rel="noreferrer"
                className="text-sm font-medium text-brand hover:underline"
              >
                {payment.current_proof.original_name}
              </a>
              {payment.current_proof.reference_number && (
                <p className="text-xs text-ink-muted">Ref: {payment.current_proof.reference_number}</p>
              )}
            </div>
          )}
        </div>

        <div>
          <p className="text-xs text-ink-muted">Payment method</p>
          <p className="mb-2 text-sm font-medium text-ink">{settlementMeta?.label ?? payment.settlement_method_label}</p>

          {isCash && (
            <p className="text-sm text-ink-muted">
              This booking is settled in cash, paid directly to the provider.
              {isProvider
                ? ' Confirm below once you have actually received it.'
                : ' Have the amount ready for the provider.'}
            </p>
          )}

          {!isCash && !payment.payment_method && (
            <p className="text-sm text-ink-muted">
              {isClient
                ? 'The provider’s payment details appear here once they accept this booking.'
                : 'Payment details appear once you accept this booking.'}
            </p>
          )}

          {!isCash && payment.payment_method && (
            <div className="space-y-2">
              <p className="text-xs text-ink-muted">Pay via {payment.payment_method.type_label}</p>
              {payment.payment_method.qr_image_url && (
                <img
                  src={payment.payment_method.qr_image_url}
                  alt="Payment QR code"
                  className="h-40 w-40 rounded-lg border border-line object-cover"
                />
              )}
              <p className="text-sm font-medium text-ink">{payment.payment_method.account_name}</p>
              {payment.payment_method.account_ref_masked && (
                <p className="text-xs text-ink-muted">{payment.payment_method.account_ref_masked}</p>
              )}
              {payment.payment_method.instructions && (
                <p className="text-xs text-ink-muted">{payment.payment_method.instructions}</p>
              )}
            </div>
          )}
        </div>
      </div>

      {isClient && !isCash && payment.payment_method && ['pending', 'rejected'].includes(payment.status) && (
        <form onSubmit={handleSubmitProof} className="mt-5 space-y-3 border-t border-line pt-4">
          <p className="text-sm font-medium text-ink">Submit your payment proof</p>
          <input
            type="file"
            accept="image/*"
            onChange={(event) => setProofFile(event.target.files?.[0] ?? null)}
            className="block w-full text-sm text-ink"
          />
          <input
            type="text"
            value={referenceNumber}
            onChange={(event) => setReferenceNumber(event.target.value)}
            placeholder="Reference number"
            className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none sm:max-w-xs"
          />
          <Button type="submit" loading={submitProofMutation.isPending}>
            Submit payment proof
          </Button>
        </form>
      )}

      {(canConfirmReceipt || canReject) && (
        <div className="mt-5 space-y-3 border-t border-line pt-4">
          {!showVerifyConfirm && (
            <div className="flex flex-wrap gap-2">
              {canConfirmReceipt && (
                <Button onClick={() => setShowVerifyConfirm(true)}>
                  {isCash ? 'Confirm cash received' : 'Verify payment'}
                </Button>
              )}
              {canReject && !showRejectForm && (
                <Button variant="outline" onClick={() => setShowRejectForm(true)}>
                  Reject payment
                </Button>
              )}
            </div>
          )}

          {showVerifyConfirm && (
            <div className="space-y-2 rounded-lg bg-slate-50 p-3">
              <p className="text-sm font-medium text-ink">
                Did you really receive the {isCash ? 'cash' : 'online'} payment
                {payment.amount != null ? ` of ₱${payment.amount}` : ''}?
              </p>
              <div className="flex gap-2">
                <Button
                  size="sm"
                  loading={verifyMutation.isPending}
                  onClick={() => verifyMutation.mutate()}
                >
                  Yes, I received it
                </Button>
                <Button size="sm" variant="outline" onClick={() => setShowVerifyConfirm(false)}>
                  Not yet
                </Button>
              </div>
            </div>
          )}

          {showRejectForm && (
            <div className="space-y-2">
              <textarea
                rows={2}
                value={rejectReason}
                onChange={(event) => setRejectReason(event.target.value)}
                placeholder="Reason for rejecting this payment"
                className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
              />
              <Button
                variant="danger"
                loading={rejectMutation.isPending}
                onClick={() => rejectMutation.mutate()}
                disabled={!rejectReason}
              >
                Confirm rejection
              </Button>
            </div>
          )}
        </div>
      )}

      {isProvider && !reportSubmitted && (
        <div className="mt-5 space-y-3 border-t border-line pt-4">
          {!showReportForm ? (
            <Button variant="outline" size="sm" onClick={() => setShowReportForm(true)}>
              Report this client
            </Button>
          ) : (
            <div className="space-y-2">
              <p className="text-sm font-medium text-ink">Report this client to admin</p>
              <select
                value={reportReason}
                onChange={(event) => setReportReason(event.target.value)}
                aria-label="Report reason"
                className="h-11 w-full rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none sm:max-w-xs"
              >
                {Object.entries(REPORT_REASON_META).map(([value, meta]) => (
                  <option key={value} value={value}>
                    {meta.label}
                  </option>
                ))}
              </select>
              <textarea
                rows={2}
                value={reportDetails}
                onChange={(event) => setReportDetails(event.target.value)}
                placeholder="Add any details that would help admin review this (optional)"
                className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
              />
              <div className="flex gap-2">
                <Button
                  variant="danger"
                  size="sm"
                  loading={reportMutation.isPending}
                  onClick={() => reportMutation.mutate()}
                >
                  Submit report
                </Button>
                <Button size="sm" variant="outline" onClick={() => setShowReportForm(false)}>
                  Cancel
                </Button>
              </div>
            </div>
          )}
        </div>
      )}
    </Card>
  );
}

/**
 * One review per completed booking (§17 / R-34..R-36): the client rates and
 * writes it, the provider may reply and may hide it. Only ever shown once
 * the booking is Completed - reviewing earlier is a 403 the backend already
 * enforces, so the client-side gate here is purely "don't show a form that
 * would just fail."
 */
function ReviewSection({ booking, isClient, isProvider }) {
  const queryClient = useQueryClient();
  const [rating, setRating] = useState(0);
  const [comment, setComment] = useState('');
  const [replyText, setReplyText] = useState('');
  const [showReplyForm, setShowReplyForm] = useState(false);
  const [notice, setNotice] = useState(null);

  const { data: review, isPending } = useQuery({
    queryKey: queryKeys.reviews.forBooking(booking.id),
    queryFn: () => reviewApi.forBooking(booking.id),
  });

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.reviews.forBooking(booking.id) });

  const submitMutation = useMutation({
    mutationFn: () => reviewApi.submit(booking.id, { rating, comment: comment || undefined }),
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Review submitted. Thank you!' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to submit your review.' }),
  });

  const replyMutation = useMutation({
    mutationFn: () => reviewApi.reply(review.id, replyText),
    onSuccess: () => {
      invalidate();
      setShowReplyForm(false);
      setNotice({ tone: 'success', message: 'Reply posted.' });
    },
    onError: (error) => setNotice({ tone: 'error', message: error?.message ?? 'Failed to post your reply.' }),
  });

  const visibilityMutation = useMutation({
    mutationFn: () => reviewApi.setVisibility(review.id, !review.is_visible),
    onSuccess: invalidate,
  });

  if (booking.status !== 'completed') {
    return null;
  }

  if (isPending) {
    return (
      <Card title="Review">
        <LoadingState label="Loading review…" />
      </Card>
    );
  }

  const handleSubmit = (event) => {
    event.preventDefault();
    setNotice(null);
    if (!rating) {
      setNotice({ tone: 'error', message: 'Please select a star rating.' });
      return;
    }
    submitMutation.mutate();
  };

  return (
    <Card title="Review">
      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {!review && isClient && (
        <form onSubmit={handleSubmit} className="space-y-3">
          <div>
            <p className="mb-1.5 text-sm font-medium text-ink">Your rating</p>
            <StarRating value={rating} onChange={setRating} />
          </div>
          <textarea
            rows={3}
            value={comment}
            onChange={(event) => setComment(event.target.value)}
            placeholder="Tell other clients about your experience (optional)"
            className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
          />
          <Button type="submit" loading={submitMutation.isPending}>
            Submit review
          </Button>
        </form>
      )}

      {!review && isProvider && (
        <p className="text-sm text-ink-muted">The client hasn’t left a review for this booking yet.</p>
      )}

      {review && (
        <div className="space-y-3">
          <div className="flex items-center gap-2">
            <StarRating value={review.rating} size="sm" />
            {!review.is_visible && <Badge tone="neutral">Hidden</Badge>}
          </div>
          {review.comment && <p className="text-sm text-ink">{review.comment}</p>}

          {review.provider_reply && (
            <div className="rounded-lg bg-slate-50 p-3">
              <p className="text-xs font-medium text-ink-muted">Provider&apos;s reply</p>
              <p className="text-sm text-ink">{review.provider_reply}</p>
            </div>
          )}

          {isProvider && (
            <div className="flex flex-wrap items-center gap-2 border-t border-line pt-3">
              {!review.provider_reply && !showReplyForm && (
                <Button size="sm" variant="outline" onClick={() => setShowReplyForm(true)}>
                  Reply
                </Button>
              )}
              <Button
                size="sm"
                variant="subtle"
                onClick={() => visibilityMutation.mutate()}
                loading={visibilityMutation.isPending}
              >
                {review.is_visible ? 'Hide review' : 'Unhide review'}
              </Button>
            </div>
          )}

          {isProvider && showReplyForm && (
            <div className="space-y-2">
              <textarea
                rows={2}
                value={replyText}
                onChange={(event) => setReplyText(event.target.value)}
                placeholder="Write a reply to this review"
                className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
              />
              <Button size="sm" loading={replyMutation.isPending} onClick={() => replyMutation.mutate()}>
                Post reply
              </Button>
            </div>
          )}
        </div>
      )}
    </Card>
  );
}

/**
 * Shared between /client/bookings/:id and /provider/bookings/:id - the API
 * resource is the same `bookings/{id}` endpoint either way, scoped by
 * BookingPolicy, so one page renders the right actions per role rather than
 * duplicating the fetch/display logic per audience.
 */
export default function BookingDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { role } = useAuth();
  const [notice, setNotice] = useState(null);
  const [cancelReason, setCancelReason] = useState('');
  const [showCancelForm, setShowCancelForm] = useState(false);

  // Polled every 5s (same cadence as ConversationThreadPage) so the other
  // party's action - client sends payment proof, provider starts/completes
  // the job - shows up live instead of needing a manual page refresh.
  const { data: booking, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.bookings.detail(id),
    queryFn: () => bookingApi.get(id),
    refetchInterval: 5_000,
  });

  // Shares its cache with PaymentSection's own useQuery below (same key) -
  // only fetched once - so the "Mark completed" gate stays in sync with
  // whatever PaymentSection just did (submit proof / verify / reject).
  const { data: payment } = useQuery({
    queryKey: queryKeys.payments.forBooking(id),
    queryFn: () => paymentApi.getForBooking(id),
    enabled: Boolean(booking),
    refetchInterval: 5_000,
  });

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: queryKeys.bookings.detail(id) });
    queryClient.invalidateQueries({ queryKey: ['bookings'] });
  };

  const backPath = role === ROLES.PROVIDER ? '/provider/bookings' : '/client/bookings';

  const actionMutation = useMutation({
    mutationFn: ({ action, payload }) => {
      if (action === 'accept') return bookingApi.accept(id);
      if (action === 'reject') return bookingApi.reject(id, payload);
      if (action === 'cancel') return bookingApi.cancel(id, payload);
      return bookingApi.updateStatus(id, payload);
    },
    onSuccess: (updated) => {
      invalidate();
      setShowCancelForm(false);
      setCancelReason('');
      setNotice({ tone: 'success', message: `Booking is now ${updated.status_label}.` });
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'That action failed.' }),
  });

  const messageMutation = useMutation({
    mutationFn: async () => {
      if (role === ROLES.CLIENT) {
        return conversationApi.start({ provider_profile_id: booking.provider.id });
      }

      // A provider cannot start a conversation (only a client can) - find
      // the existing thread with this booking's client instead.
      const conversations = await conversationApi.list();
      const existing = conversations.find((c) => c.other_participant?.id === booking.client?.id);

      if (!existing) {
        throw { message: 'This client has not started a conversation with you yet.' };
      }

      return existing;
    },
    onSuccess: (conversation) => {
      navigate(`/${role}/messages/${conversation.id}`);
    },
    onError: (err) => setNotice({ tone: 'error', message: err?.message ?? 'Could not open the conversation.' }),
  });

  if (isPending) {
    return <LoadingState label="Loading booking…" className="mt-10" />;
  }

  if (isError) {
    return <ErrorState description={error?.message} onRetry={() => refetch()} className="mt-10" />;
  }

  const canTransitionTo = (target) => (BOOKING_TRANSITIONS[booking.status] ?? []).includes(target);
  const isProvider = role === ROLES.PROVIDER;
  const isClient = role === ROLES.CLIENT;
  const price = booking.final_price ?? booking.quoted_price;

  return (
    <div className="mx-auto max-w-4xl space-y-5">
      {notice && <Alert tone={notice.tone}>{notice.message}</Alert>}

      <Card>
        {/* Summary bar: the two things worth seeing at a glance, code/service
            on the left, status/price on the right - everything else below is
            detail, not headline. */}
        <div className="flex flex-wrap items-start justify-between gap-4 border-b border-line px-5 py-4">
          <div>
            <p className="text-xs text-ink-muted">{booking.booking_code}</p>
            <p className="font-display text-lg font-semibold text-navy-800">{booking.service?.title}</p>
          </div>
          <div className="text-right">
            <StatusBadge status={booking.status} />
            <p className="mt-1 font-display text-xl font-bold text-navy-800">
              {price != null ? `₱${price}` : '—'}
            </p>
          </div>
        </div>

        <div className="grid gap-x-8 gap-y-5 px-5 py-5 md:grid-cols-2">
          {/* Left column: who, when, and any notes on the booking itself. */}
          <div className="space-y-5">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-xs text-ink-muted">{isProvider ? 'Client' : 'Provider'}</p>
                <p className="font-medium text-ink">
                  {isProvider ? booking.client?.full_name : booking.provider?.business_name}
                </p>
              </div>
              <div>
                <p className="text-xs text-ink-muted">Date &amp; time</p>
                <p className="font-medium text-ink">
                  {booking.scheduled_date}
                  <br />
                  {booking.scheduled_start_time?.slice(0, 5)}–{booking.scheduled_end_time?.slice(0, 5)}
                </p>
              </div>
            </div>

            {booking.client_notes && (
              <div>
                <p className="text-xs text-ink-muted">Client notes</p>
                <p className="text-sm text-ink">{booking.client_notes}</p>
              </div>
            )}

            {booking.cancellation_reason && (
              <div>
                <p className="text-xs text-ink-muted">Cancellation reason</p>
                <p className="text-sm text-ink">{booking.cancellation_reason}</p>
              </div>
            )}
          </div>

          {/* Right column: where - kept together since the map only makes
              sense alongside the address that anchors it. */}
          <div>
            <p className="mb-2 text-xs text-ink-muted">Service location</p>
            {booking.location ? (
              <div className="space-y-2">
                <p className="text-sm text-ink">
                  {booking.location.address_line}
                  {booking.location.barangay?.name ? `, ${booking.location.barangay.name}` : ''}
                </p>
                {booking.location.landmark_notes && (
                  <p className="text-xs text-ink-muted">{booking.location.landmark_notes}</p>
                )}
                <LocationView latitude={booking.location.latitude} longitude={booking.location.longitude} height={200} />
              </div>
            ) : (
              <p className="text-sm text-ink-muted">
                {isProvider
                  ? 'The exact address appears here once you accept this booking.'
                  : 'Not available.'}
              </p>
            )}
          </div>
        </div>

        <div className="flex flex-wrap gap-2 border-t border-line px-5 py-4">
          {isProvider && canTransitionTo('accepted') && (
            <Button onClick={() => actionMutation.mutate({ action: 'accept' })} loading={actionMutation.isPending}>
              Accept
            </Button>
          )}
          {isProvider && canTransitionTo('rejected') && (
            <Button
              variant="danger"
              onClick={() => actionMutation.mutate({ action: 'reject' })}
              loading={actionMutation.isPending}
            >
              Reject
            </Button>
          )}
          {isProvider && canTransitionTo('in_progress') && (
            <Button
              onClick={() => actionMutation.mutate({ action: 'status', payload: 'in_progress' })}
              loading={actionMutation.isPending}
            >
              Start job
            </Button>
          )}
          {isProvider && canTransitionTo('completed') && payment?.status === 'verified' && (
            <Button
              onClick={() => actionMutation.mutate({ action: 'status', payload: 'completed' })}
              loading={actionMutation.isPending}
            >
              Mark completed
            </Button>
          )}
          {isProvider && canTransitionTo('completed') && payment && payment.status !== 'verified' && (
            <Button disabled title="Confirm you received the payment (see the Payment section below) before completing this job.">
              Mark completed
            </Button>
          )}
          {(isClient || isProvider) && canTransitionTo('cancelled') && !showCancelForm && (
            <Button variant="outline" onClick={() => setShowCancelForm(true)}>
              Cancel booking
            </Button>
          )}
          {(isClient || isProvider) && (
            <Button
              variant="outline"
              onClick={() => messageMutation.mutate()}
              loading={messageMutation.isPending}
            >
              Message {isClient ? 'provider' : 'client'}
            </Button>
          )}
          <Button variant="subtle" onClick={() => navigate(backPath)}>
            Back to bookings
          </Button>
        </div>

        {showCancelForm && (
          <div className="space-y-2 border-t border-line px-5 py-4">
            {booking.status === 'accepted' && (
              <p className="text-xs text-ink-muted">A reason is required once a booking has been accepted.</p>
            )}
            <textarea
              rows={2}
              value={cancelReason}
              onChange={(event) => setCancelReason(event.target.value)}
              placeholder="Reason for cancelling"
              className="w-full rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink focus:border-navy-500 focus:outline-none"
            />
            <Button
              variant="danger"
              loading={actionMutation.isPending}
              onClick={() => actionMutation.mutate({ action: 'cancel', payload: cancelReason || undefined })}
            >
              Confirm cancellation
            </Button>
          </div>
        )}
      </Card>

      <PaymentSection booking={booking} isClient={isClient} isProvider={isProvider} />
      <ReviewSection booking={booking} isClient={isClient} isProvider={isProvider} />
    </div>
  );
}
