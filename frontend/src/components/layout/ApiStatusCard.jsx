import { useQuery } from '@tanstack/react-query';
import { api, unwrap } from '@/services/api/client';
import { queryKeys } from '@/services/api/queryClient';
import { Card, CardBody, CardHeader } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { LoadingState, ErrorState } from '@/components/ui/States';

/**
 * Live end-to-end connectivity check: React -> Axios -> Laravel -> MySQL.
 *
 * This is real data, not a placeholder. It is the Phase 1 exit evidence that
 * the whole request lifecycle is wired, and it stays useful afterwards as a
 * quick diagnostic on every dashboard.
 */
export function ApiStatusCard() {
  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.health,
    queryFn: async () => unwrap(await api.get('/health')),
    staleTime: 15_000,
  });

  return (
    <Card>
      <CardHeader title="System status" />
      <CardBody>
        {isPending && <LoadingState label="Checking the API…" className="py-6" />}

        {isError && (
          <ErrorState
            title="Cannot reach the API"
            description={error?.message}
            onRetry={() => refetch()}
            className="py-6"
          />
        )}

        {data && (
          <dl className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
              <dt className="text-xs text-ink-muted">API</dt>
              <dd className="mt-1">
                <Badge tone={data.status === 'ok' ? 'success' : 'warning'}>
                  {data.status === 'ok' ? 'Online' : 'Degraded'}
                </Badge>
              </dd>
            </div>
            <div>
              <dt className="text-xs text-ink-muted">Database</dt>
              <dd className="mt-1">
                <Badge tone={data.database === 'up' ? 'success' : 'danger'}>
                  {data.database === 'up' ? 'Connected' : 'Unreachable'}
                </Badge>
              </dd>
            </div>
            <div>
              <dt className="text-xs text-ink-muted">Environment</dt>
              <dd className="mt-1 text-sm font-medium text-ink">{data.environment}</dd>
            </div>
            <div>
              <dt className="text-xs text-ink-muted">Server time</dt>
              <dd className="mt-1 text-sm font-medium text-ink">
                {new Date(data.time).toLocaleTimeString()}
              </dd>
            </div>
          </dl>
        )}
      </CardBody>
    </Card>
  );
}
