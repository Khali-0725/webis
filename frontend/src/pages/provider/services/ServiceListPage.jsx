import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Alert } from '@/components/ui/Alert';
import { Card } from '@/components/ui/Card';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { TrashToggle, DeletedBadge } from '@/components/ui/TrashToggle';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { providerApi } from '@/services/api/providerApi';
import { queryKeys } from '@/services/api/queryClient';
import { PRICING_TYPE_META } from '@/constants';

export default function ServiceListPage() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [trashed, setTrashed] = useState('');
  const [notice, setNotice] = useState(null);
  const [pendingDelete, setPendingDelete] = useState(null);
  const inTrash = trashed === 'only';
  const params = { ...(trashed ? { trashed } : {}), page };

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: [...queryKeys.provider.services, params],
    queryFn: () => providerApi.listMyServices(params),
  });

  const profileQuery = useQuery({
    queryKey: queryKeys.provider.profile,
    queryFn: providerApi.getMyProfile,
  });

  const isVerified = profileQuery.data?.verification_status === 'approved';

  const invalidate = () => queryClient.invalidateQueries({ queryKey: queryKeys.provider.services });
  const onError = (fallback) => (err) => setNotice({ tone: 'error', message: err?.message ?? fallback });

  const publishMutation = useMutation({ mutationFn: providerApi.publishService, onSuccess: invalidate, onError: onError('Failed to publish service.') });
  const deactivateMutation = useMutation({ mutationFn: providerApi.deactivateService, onSuccess: invalidate, onError: onError('Failed to deactivate service.') });
  const restoreMutation = useMutation({
    mutationFn: providerApi.restoreService,
    onSuccess: () => {
      invalidate();
      setNotice({ tone: 'success', message: 'Service restored. It comes back as it was - re-publish it if it was live before.' });
    },
    onError: onError('Failed to restore service.'),
  });
  const deleteMutation = useMutation({
    mutationFn: providerApi.deleteService,
    onSuccess: () => {
      invalidate();
      setPendingDelete(null);
      setNotice({ tone: 'success', message: 'Service moved to trash.' });
    },
  });

  const items = data?.items ?? [];

  return (
    <Card
      title="My Services"
      action={
        <div className="flex items-center gap-2">
          <TrashToggle value={trashed} onChange={(v) => { setTrashed(v); setPage(1); }} />
          {isVerified && (
            <Link to="/provider/services/new">
              <Button size="sm">Create service</Button>
            </Link>
          )}
        </div>
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

      {notice && (
        <Alert tone={notice.tone} className="mb-4">
          {notice.message}
        </Alert>
      )}

      {isPending && <LoadingState label="Loading services…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && items.length === 0 && (
        <EmptyState
          title={inTrash ? 'Trash is empty' : 'No services yet'}
          description={
            inTrash
              ? 'Services you delete will appear here until you restore them.'
              : isVerified
                ? 'Create your first service to start getting booked.'
                : 'Once your provider account is verified, you can add your first service.'
          }
          action={
            !inTrash && isVerified ? (
              <Link to="/provider/services/new">
                <Button size="sm">Create a service</Button>
              </Link>
            ) : null
          }
        />
      )}

      {!isPending && !isError && items.length > 0 && (
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
              {items.map((service) => (
                <tr key={service.id}>
                  <td className="py-3 pr-4 text-ink">{service.title}</td>
                  <td className="py-3 pr-4 text-ink-muted">{service.category?.name}</td>
                  <td className="py-3 pr-4 text-ink-muted">
                    {service.pricing_type === 'quote' ? 'Quote' : `₱${service.price}`}
                    <span className="ml-1 text-xs">({PRICING_TYPE_META[service.pricing_type]?.label})</span>
                  </td>
                  <td className="py-3 pr-4">
                    <div className="flex flex-wrap gap-1.5">
                      {service.deleted_at ? (
                        <DeletedBadge deletedAt={service.deleted_at} />
                      ) : (
                        <>
                          <Badge tone={service.is_published ? 'success' : 'neutral'}>
                            {service.is_published ? 'Published' : 'Draft'}
                          </Badge>
                          {!service.is_active && <Badge tone="danger">Inactive</Badge>}
                        </>
                      )}
                    </div>
                  </td>
                  <td className="py-3">
                    {service.deleted_at ? (
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => restoreMutation.mutate(service.id)}
                        loading={restoreMutation.isPending && restoreMutation.variables === service.id}
                      >
                        Restore
                      </Button>
                    ) : (
                      <div className="flex flex-wrap gap-2">
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
                        <Button size="sm" variant="danger" onClick={() => setPendingDelete(service)}>
                          Delete
                        </Button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination meta={data?.meta} onChange={setPage} />

      <ConfirmDialog
        open={Boolean(pendingDelete)}
        onClose={() => {
          setPendingDelete(null);
          deleteMutation.reset();
        }}
        onConfirm={() => deleteMutation.mutate(pendingDelete.id)}
        title="Delete this service?"
        description={pendingDelete?.title}
        confirmLabel="Delete service"
        tone="danger"
        loading={deleteMutation.isPending}
        error={deleteMutation.error?.message}
      >
        <p className="text-sm text-ink-muted">
          Clients will no longer find it in search. Bookings already made against it are unaffected. You
          can restore it from the Trash view at any time.
        </p>
      </ConfirmDialog>
    </Card>
  );
}
