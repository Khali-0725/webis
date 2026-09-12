import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { providerApi } from '@/services/api/admin/providerApi';
import { queryKeys } from '@/services/api/queryClient';

const STATUS_TONE = { pending: 'warning', approved: 'success', rejected: 'danger' };

export default function ProviderDetailPage() {
  const { id } = useParams();

  const { data: provider, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.admin.provider(id),
    queryFn: () => providerApi.show(id),
  });

  if (isPending) return <LoadingState label="Loading provider…" />;
  if (isError) return <ErrorState description={error?.message} onRetry={() => refetch()} />;

  return (
    <div className="space-y-5">
      <Card title={provider.business_name}>
        <dl className="grid gap-4 sm:grid-cols-2">
          <div>
            <dt className="text-xs uppercase tracking-wide text-ink-muted">Owner</dt>
            <dd className="text-ink">{provider.owner?.full_name}</dd>
            <dd className="text-sm text-ink-muted">{provider.owner?.email}</dd>
          </div>
          <div>
            <dt className="text-xs uppercase tracking-wide text-ink-muted">Verification</dt>
            <dd>
              <Badge tone={STATUS_TONE[provider.verification_status] ?? 'neutral'}>
                {provider.verification_status_label}
              </Badge>
            </dd>
          </div>
          <div>
            <dt className="text-xs uppercase tracking-wide text-ink-muted">Rating</dt>
            <dd className="text-ink">
              {provider.rating_avg ? `${provider.rating_avg} (${provider.rating_count} reviews)` : 'No reviews yet'}
            </dd>
          </div>
          <div>
            <dt className="text-xs uppercase tracking-wide text-ink-muted">Completed bookings</dt>
            <dd className="text-ink">{provider.completed_bookings_count}</dd>
          </div>
          <div>
            <dt className="text-xs uppercase tracking-wide text-ink-muted">Base barangay</dt>
            <dd className="text-ink">{provider.base_barangay ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-xs uppercase tracking-wide text-ink-muted">Recent bookings</dt>
            <dd className="text-ink">{provider.recent_bookings_count}</dd>
          </div>
        </dl>
      </Card>

      <Card title="Services">
        {provider.services?.length ? (
          <ul className="divide-y divide-line">
            {provider.services.map((s) => (
              <li key={s.id} className="flex items-center justify-between py-2 text-sm">
                <span className="text-ink">{s.title}</span>
                <Badge tone={s.is_active ? 'success' : 'neutral'}>{s.is_active ? 'Active' : 'Inactive'}</Badge>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-sm text-ink-muted">No services listed.</p>
        )}
      </Card>

      <Card title="Verification Documents">
        {provider.verification_documents?.length ? (
          <ul className="divide-y divide-line">
            {provider.verification_documents.map((d) => (
              <li key={d.id} className="flex items-center justify-between py-2 text-sm">
                <span className="text-ink">{d.document_type}</span>
                <Badge tone={d.status === 'approved' ? 'success' : d.status === 'rejected' ? 'danger' : 'warning'}>
                  {d.status}
                </Badge>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-sm text-ink-muted">No documents submitted.</p>
        )}
      </Card>
    </div>
  );
}
