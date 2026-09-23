import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { StarRating } from '@/components/ui/StarRating';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { publicApi } from '@/services/api/publicApi';
import { reviewApi } from '@/services/api/reviewApi';
import { queryKeys } from '@/services/api/queryClient';

function formatMonthYear(dateString) {
  if (!dateString) return '';
  return new Date(`${dateString}T00:00:00`).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function RatingDistribution({ distribution, total }) {
  return (
    <div className="space-y-1">
      {Object.entries(distribution).map(([star, count]) => (
        <div key={star} className="flex items-center gap-2 text-xs text-ink-muted">
          <span className="w-10 shrink-0">{star} star</span>
          <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
            <div
              className="h-full rounded-full bg-amber-400"
              style={{ width: total > 0 ? `${(count / total) * 100}%` : '0%' }}
            />
          </div>
          <span className="w-6 shrink-0 text-right">{count}</span>
        </div>
      ))}
    </div>
  );
}

export default function ProviderProfilePage() {
  const { id } = useParams();
  const [reviewsPage, setReviewsPage] = useState(1);

  const {
    data: provider,
    isPending,
    isError,
    error,
    refetch,
  } = useQuery({
    queryKey: queryKeys.public.provider(id),
    queryFn: () => publicApi.getProvider(id),
  });

  const reviewsQuery = useQuery({
    queryKey: queryKeys.public.providerReviews(id, { page: reviewsPage }),
    queryFn: () => reviewApi.forProvider(id, { page: reviewsPage }),
    enabled: Boolean(provider),
  });

  if (isPending) {
    return (
      <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <LoadingState label="Loading provider…" />
      </div>
    );
  }

  if (isError) {
    return (
      <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <ErrorState description={error?.message} onRetry={() => refetch()} />
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6">
      <div className="webis-card p-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="font-display text-2xl font-bold text-navy-800">
              {provider.business_name}
            </h1>
            {(provider.base_barangay || provider.age != null) && (
              <p className="mt-1 text-sm text-ink-muted">
                {[provider.base_barangay?.name, provider.age != null ? `${provider.age} years old` : null]
                  .filter(Boolean)
                  .join(' · ')}
              </p>
            )}
          </div>
          {provider.rating_count > 0 && (
            <Badge tone="success">★ {provider.rating_avg} ({provider.rating_count} reviews)</Badge>
          )}
        </div>

        {provider.bio && <p className="mt-4 text-sm leading-relaxed text-ink-muted">{provider.bio}</p>}

        {provider.skills?.length > 0 && (
          <div className="mt-4 flex flex-wrap gap-2">
            {provider.skills.map((skill) => (
              <Badge key={skill} tone="neutral">
                {skill}
              </Badge>
            ))}
          </div>
        )}
      </div>

      <h2 className="mt-8 font-display text-xl font-bold text-navy-800">Services</h2>

      {provider.services.length === 0 ? (
        <EmptyState
          className="mt-4"
          title="No published services"
          description="This provider hasn't published any services yet."
        />
      ) : (
        <ul className="mt-4 space-y-3">
          {provider.services.map((service) => (
            <li key={service.id}>
              <Link to={`/services/${service.id}`} className="webis-card flex items-center justify-between p-4">
                <div>
                  <p className="text-xs font-medium uppercase tracking-wide text-brand">
                    {service.category?.name}
                  </p>
                  <p className="font-semibold text-navy-800">{service.title}</p>
                </div>
                <p className="font-display font-bold text-navy-800">
                  {service.pricing_type === 'quote' ? 'Quote' : `₱${service.price}`}
                </p>
              </Link>
            </li>
          ))}
        </ul>
      )}

      {provider.work_experiences?.length > 0 && (
        <>
          <h2 className="mt-8 font-display text-xl font-bold text-navy-800">Work Experience</h2>
          <ul className="mt-4 space-y-3">
            {provider.work_experiences.map((experience) => (
              <li key={experience.id} className="webis-card p-4">
                <p className="font-semibold text-navy-800">
                  {experience.role_title}
                  {experience.employer_name ? ` · ${experience.employer_name}` : ''}
                </p>
                <p className="text-sm text-ink-muted">
                  {formatMonthYear(experience.started_on)} –{' '}
                  {experience.is_current ? 'Present' : formatMonthYear(experience.ended_on)}
                </p>
                {experience.description && (
                  <p className="mt-1 text-sm text-ink-muted">{experience.description}</p>
                )}
              </li>
            ))}
          </ul>
        </>
      )}

      <h2 className="mt-8 font-display text-xl font-bold text-navy-800">Reviews</h2>

      {reviewsQuery.isPending && <LoadingState className="mt-4" label="Loading reviews…" />}

      {reviewsQuery.isSuccess && reviewsQuery.data.items.length === 0 && (
        <EmptyState className="mt-4" title="No reviews yet" description="This provider hasn't been reviewed yet." />
      )}

      {reviewsQuery.isSuccess && reviewsQuery.data.items.length > 0 && (
        <div className="mt-4 grid gap-6 sm:grid-cols-[200px_1fr]">
          <RatingDistribution
            distribution={reviewsQuery.data.meta.distribution}
            total={provider.rating_count}
          />

          <ul className="space-y-3">
            {reviewsQuery.data.items.map((review) => (
              <li key={review.id} className="webis-card p-4">
                <div className="flex items-center justify-between gap-3">
                  <p className="font-medium text-ink">{review.client?.full_name}</p>
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
        </div>
      )}

      {reviewsQuery.isSuccess && reviewsQuery.data.items.length > 0 && (
        <Pagination meta={reviewsQuery.data.meta} onChange={setReviewsPage} />
      )}
    </div>
  );
}
