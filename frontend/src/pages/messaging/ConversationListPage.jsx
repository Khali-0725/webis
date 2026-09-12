import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { Avatar } from '@/components/ui/Avatar';
import { LoadingState, ErrorState, EmptyState } from '@/components/ui/States';
import { conversationApi } from '@/services/api/conversationApi';
import { queryKeys } from '@/services/api/queryClient';
import { useAuth } from '@/hooks/useAuth';
import { ROLES } from '@/constants';

export default function ConversationListPage() {
  const { role } = useAuth();
  const basePath = role === ROLES.PROVIDER ? '/provider/messages' : '/client/messages';

  const { data: conversations = [], isPending, isError, error, refetch } = useQuery({
    queryKey: queryKeys.conversations.list,
    queryFn: conversationApi.list,
    refetchInterval: 30_000,
  });

  return (
    <Card title="Messages">
      {isPending && <LoadingState label="Loading conversations…" />}
      {isError && <ErrorState description={error?.message} onRetry={() => refetch()} />}

      {!isPending && !isError && conversations.length === 0 && (
        <EmptyState
          title="No conversations yet"
          description="Message a provider from their service page to start a conversation."
        />
      )}

      {!isPending && !isError && conversations.length > 0 && (
        <ul className="divide-y divide-line">
          {conversations.map((conversation) => (
            <li key={conversation.id}>
              <Link
                to={`${basePath}/${conversation.id}`}
                className="flex items-center justify-between gap-3 py-3 hover:bg-slate-50"
              >
                <Avatar
                  src={conversation.other_participant?.avatar_url}
                  initials={conversation.other_participant?.initials}
                  name={conversation.other_participant?.full_name}
                  size="md"
                />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-ink">
                    {conversation.other_participant?.full_name ?? 'Unknown user'}
                  </p>
                  <p className="truncate text-xs text-ink-muted">
                    {conversation.last_message_preview ?? 'No messages yet'}
                  </p>
                </div>
                {conversation.unread_count > 0 && (
                  <Badge tone="brand">{conversation.unread_count}</Badge>
                )}
              </Link>
            </li>
          ))}
        </ul>
      )}
    </Card>
  );
}
