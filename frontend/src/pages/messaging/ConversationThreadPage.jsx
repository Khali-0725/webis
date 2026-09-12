import { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/ui/Alert';
import { Avatar } from '@/components/ui/Avatar';
import { LoadingState, ErrorState } from '@/components/ui/States';
import { conversationApi } from '@/services/api/conversationApi';
import { queryKeys } from '@/services/api/queryClient';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';
import { ROLES } from '@/constants';

const QUICK_REPLIES = {
  [ROLES.CLIENT]: [
    'What time will you arrive?',
    'Is the schedule still available?',
    'Can we reschedule this booking?',
    'How much will this cost in total?',
    "I'm running a few minutes late.",
    'Thank you, see you then!',
  ],
  [ROLES.PROVIDER]: [
    "I'm on my way, arriving in about 15 minutes.",
    "I've arrived at the location.",
    'Could you confirm the exact address or a nearby landmark?',
    'Sorry, I need to reschedule. Are you free another day?',
    'The service is complete. Thank you for booking!',
    'Please check the QR code for payment details.',
  ],
};

export default function ConversationThreadPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { role } = useAuth();
  const [body, setBody] = useState('');
  const [confirmPrompt, setConfirmPrompt] = useState(null);
  const [formError, setFormError] = useState(null);
  const bottomRef = useRef(null);

  const messagesQuery = useQuery({
    queryKey: queryKeys.conversations.messages(id),
    queryFn: () => conversationApi.listMessages(id),
    refetchInterval: 5_000,
  });

  // Always jump to the newest message - on first load, after sending, and
  // on every poll tick that brings in a new one. A plain top-to-bottom list
  // is far less fragile than the flex-col-reverse "anchor to bottom" trick,
  // which only works when nothing else in the DOM order fights it.
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ block: 'end' });
  }, [messagesQuery.data]);

  useEffect(() => {
    conversationApi.markRead(id).then(() => {
      queryClient.invalidateQueries({ queryKey: queryKeys.conversations.list });
      queryClient.invalidateQueries({ queryKey: queryKeys.conversations.unreadCount });
    });
  }, [id, queryClient]);

  const sendMutation = useMutation({
    mutationFn: ({ text, confirmOverride }) => conversationApi.sendMessage(id, { body: text, confirm_override: confirmOverride }),
    onSuccess: (result) => {
      if (result?.requires_confirmation) {
        setConfirmPrompt(body);
        return;
      }

      setBody('');
      setConfirmPrompt(null);
      queryClient.invalidateQueries({ queryKey: queryKeys.conversations.messages(id) });
      queryClient.invalidateQueries({ queryKey: queryKeys.conversations.list });
    },
    onError: (error) => setFormError(error?.message ?? 'Failed to send the message.'),
  });

  const handleSubmit = (event) => {
    event.preventDefault();
    setFormError(null);
    if (!body.trim()) return;

    sendMutation.mutate({ text: body, confirmOverride: false });
  };

  const handleSendAnyway = () => {
    setFormError(null);
    sendMutation.mutate({ text: confirmPrompt, confirmOverride: true });
  };

  if (messagesQuery.isPending) {
    return <LoadingState label="Loading conversation…" className="mt-10" />;
  }

  if (messagesQuery.isError) {
    return (
      <ErrorState
        description={messagesQuery.error?.message}
        onRetry={() => messagesQuery.refetch()}
        className="mt-10"
      />
    );
  }

  // One single chronological timeline, oldest first - never grouped by
  // sender. Sorted explicitly by created_at rather than relying on the
  // page's id order, so this is correct regardless of how the API orders
  // or paginates its response.
  const messages = [...messagesQuery.data.items].sort(
    (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime(),
  );

  return (
    <Card title="Conversation">
      <div className="flex max-h-[60vh] flex-col gap-3 overflow-y-auto rounded-lg border border-line bg-slate-50 p-4">
        {messages.length === 0 && (
          <p className="text-center text-sm text-ink-muted">No messages yet. Say hello!</p>
        )}
        {messages.map((message) => (
          <div
            key={message.id}
            className={cn('flex w-full items-end gap-2', message.is_mine ? 'justify-end' : 'justify-start')}
          >
            {!message.is_mine && (
              <Avatar
                src={message.sender?.avatar_url}
                initials={message.sender?.initials}
                name={message.sender?.full_name}
                size="sm"
              />
            )}
            <div className={cn('flex max-w-[75%] flex-col', message.is_mine ? 'items-end' : 'items-start')}>
              <p className="mb-0.5 px-1 text-[11px] font-medium text-ink-muted">
                {message.sender?.full_name ?? 'Unknown user'}
              </p>
              <div
                className={cn(
                  'rounded-lg px-3 py-2 text-sm',
                  message.is_mine ? 'bg-navy-700 text-white' : 'bg-white text-ink shadow-sm',
                  message.is_flagged && 'opacity-70',
                )}
              >
                {message.body}
                {message.is_flagged && (
                  <p className="mt-1 text-[11px] italic opacity-80">Flagged for review</p>
                )}
              </div>
            </div>
            {message.is_mine && (
              <Avatar
                src={message.sender?.avatar_url}
                initials={message.sender?.initials}
                name={message.sender?.full_name}
                size="sm"
              />
            )}
          </div>
        ))}
        <div ref={bottomRef} />
      </div>

      {formError && (
        <Alert tone="error" className="mt-3">
          {formError}
        </Alert>
      )}

      {confirmPrompt && (
        <Alert tone="warning" className="mt-3">
          <p>This looks like it may contain contact information — edit or send anyway?</p>
          <div className="mt-2 flex gap-2">
            <Button size="sm" variant="danger" onClick={handleSendAnyway} loading={sendMutation.isPending}>
              Send anyway
            </Button>
            <Button size="sm" variant="outline" onClick={() => setConfirmPrompt(null)}>
              Edit message
            </Button>
          </div>
        </Alert>
      )}

      {QUICK_REPLIES[role]?.length > 0 && (
        <div className="mt-3 flex flex-wrap gap-2">
          {QUICK_REPLIES[role].map((reply) => (
            <button
              key={reply}
              type="button"
              onClick={() => {
                setBody(reply);
                setConfirmPrompt(null);
              }}
              className="rounded-full border border-line bg-white px-3 py-1.5 text-xs text-ink-muted transition-colors hover:border-navy-300 hover:text-ink"
            >
              {reply}
            </button>
          ))}
        </div>
      )}

      <form onSubmit={handleSubmit} className="mt-3 flex gap-2">
        <input
          type="text"
          value={body}
          onChange={(event) => {
            setBody(event.target.value);
            setConfirmPrompt(null);
          }}
          placeholder="Type a message…"
          maxLength={2000}
          className="h-11 flex-1 rounded-lg border border-line bg-white px-3 text-sm text-ink focus:border-navy-500 focus:outline-none"
        />
        <Button type="submit" loading={sendMutation.isPending && !confirmPrompt}>
          Send
        </Button>
      </form>

      <Button variant="subtle" className="mt-3" onClick={() => navigate(-1)}>
        Back
      </Button>
    </Card>
  );
}
