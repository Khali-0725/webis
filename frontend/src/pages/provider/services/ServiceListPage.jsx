import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Alert } from '@/components/ui/Alert';
import { Card } from '@/components/ui/Card';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { providerApi } from '@/services/api/providerApi';
import { queryKeys } from '@/services/api/queryClient';
import { PRICING_TYPE_META } from '@/constants';

export default function ServiceListPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: [...queryKeys.provider.services, page],
    queryFn: () => providerApi.listMyServices({ page }),
  });

  const profileQuery = useQuery({
    queryKey: queryKeys.provider.profile,
    queryFn: providerApi.getMyProfile,
  });

  const isVerified = profileQuery.data?.verification_status === 'approved';

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.provider.services });

  const publishMutation = useMutation({
    mutationFn: providerApi.publishService,
    onSuccess: invalidate,
  });

  const deactivateMutation = useMutation({
    mutationFn: providerApi.deactivateService,
    onSuccess: invalidate,
  });

  return (
    <Card
      title="My Services"
      action={
        isVerified ? (
          <Link to="/provider/services/new">
            <Button size="sm">Create service</Button>
          </Link>
        ) : null
      }
    >
      {!profileQuery.isPending && !isVerified && (
        <Alert tone="warning" title="Verification required" className="mb-4">
          You need an approved verification before you can add a service.{' '}
          <Link to="/provider/verification" className="font-semibold underline">
            Go to verification
          </Link>
          .
        </Alert>
      )}

      {publishMutation.isError && (
        <p className="mb-4 text-sm font-medium text-red-600">{publishMutation.error?.message}</p>
      )}

      {isPending && <LoadingState label="Loading services…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && data.items.length === 0 && (
        <EmptyState
          title="No services yet"
          description={
            isVerified
              ? 'Create your first service to start getting booked.'
              : 'Once your provider account is verified, you can add your first service.'
          }
          action={
            isVerified ? (
              <Link to="/provider/services/new">
                <Button size="sm">Create a service</Button>
              </Link>
            ) : null
          }
        />
      )}

      {!isPending && !isError && data.items.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                <th className="py-2 pr-4 font-medium">Title</th>
                <th className="py-2 pr-4 font-medium">Category</th>
                <th className="py-2 pr-4 font-medium">Price</th>
                <th className="py-2 pr-4 font-medium">Status</th>
                <th className="py-2 font-medium">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {data.items.map((service) => (
                <tr key={service.id}>
                  <td className="py-3 pr-4 text-ink">{service.title}</td>
                  <td className="py-3 pr-4 text-ink-muted">{service.category?.name}</td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {service.pricing_type === 'quote' ? 'Quote' : `₱${service.price}`}
                    <span className="ml-1 text-xs">({PRICING_TYPE_META[service.pricing_type]?.label})</span>
                  </td>
                  <td className="py-3 pr-4">
                    <div className="flex gap-1.5">
                      <Badge tone={service.is_published ? 'success' : 'neutral'}>
                        {service.is_published ? 'Published' : 'Draft'}
                      </Badge>
                      {!service.is_active && <Badge tone="danger">Inactive</Badge>}
                    </div>
                  </td>
                  <td className="py-3">
                    <div className="flex gap-2">
                      <Link to={`/provider/services/${service.id}/edit`}>
                        <Button size="sm" variant="outline">
                          Edit
                        </Button>
                      </Link>
                      {!service.is_published && (
                        <Button
                          size="sm"
                          onClick={() => publishMutation.mutate(service.id)}
                          loading={publishMutation.isPending && publishMutation.variables === service.id}
                        >
                          Publish
                        </Button>
                      )}
                      {service.is_active && (
                        <Button
                          size="sm"
                          variant="subtle"
                          onClick={() => deactivateMutation.mutate(service.id)}
                          loading={deactivateMutation.isPending && deactivateMutation.variables === service.id}
                        >
                          Deactivate
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />
    </Card>
  );
}
