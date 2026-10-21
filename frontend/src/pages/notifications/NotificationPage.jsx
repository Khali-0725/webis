import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Pagination } from '@/components/ui/Pagination';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { notificationApi } from '@/services/api/notificationApi';
import { useAuth } from '@/hooks/useAuth';

export default function NotificationPage() {
  const [page, setPage] = useState(1);
  const queryClient = useQueryClient();
  const { role } = useAuth();
  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['notifications', page],
    queryFn: () => notificationApi.list({ page }),
    refetchInterval: 30000,
  });
  const readMutation = useMutation({
    mutationFn: notificationApi.markRead,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  return (
    <Card title="Notifications">
      {role !== 'admin' && <Link to={`/${role}/messages`} className="mb-4 inline-block text-sm font-medium text-brand hover:underline">View messages</Link>}
      {isPending && <LoadingState label="Loading notifications…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}
      {!isPending && !isError && data.items.length === 0 && <EmptyState title="No notifications yet" description="Booking and payment updates will appear here." />}
      {!isPending && !isError && data.items.length > 0 && (
        <ul className="divide-y divide-line">
          {data.items.map((item) => (
            <li key={item.id} className={`py-4 ${item.read_at ? '' : 'font-semibold'}`}>
              <Link to={item.data?.url || '#'} onClick={() => !item.read_at && readMutation.mutate(item.id)} className="text-ink hover:text-brand">
                {item.data?.message || 'Account update'}
              </Link>
              <p className="mt-1 text-xs font-normal text-ink-muted">{new Date(item.created_at).toLocaleString()}</p>
            </li>
          ))}
        </ul>
      )}
      <Pagination meta={data?.meta} onChange={setPage} />
    </Card>
  );
}
