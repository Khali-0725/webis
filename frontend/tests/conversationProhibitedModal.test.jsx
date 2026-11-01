import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Routes, Route } from 'react-router-dom';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ConversationThreadPage from '@/pages/messaging/ConversationThreadPage';
import { conversationApi } from '@/services/api/conversationApi';
import { makeUser, renderWithProviders, resetSession, setSession } from './utils';

vi.mock('@/services/api/conversationApi', () => ({
  conversationApi: {
    listMessages: vi.fn(),
    markRead: vi.fn(),
    sendMessage: vi.fn(),
    updateMessage: vi.fn(),
    deleteMessage: vi.fn(),
  },
}));

const prohibited = {
  status: 422,
  message: 'This message was not sent because it contains inappropriate language. Please edit it and try again.',
};

function renderThread() {
  return renderWithProviders(
    <Routes><Route path="/conversations/:id" element={<ConversationThreadPage />} /></Routes>,
    { route: '/conversations/42' },
  );
}

describe('prohibited chat message warning', () => {
  beforeEach(() => {
    resetSession();
    vi.clearAllMocks();
    setSession(makeUser());
    Element.prototype.scrollIntoView = vi.fn();
    conversationApi.markRead.mockResolvedValue(null);
    conversationApi.listMessages.mockResolvedValue({ items: [], meta: null });
  });

  it('shows a dismissible warning modal after a blocked send and keeps the draft', async () => {
    const user = userEvent.setup();
    conversationApi.sendMessage.mockRejectedValue(prohibited);
    renderThread();

    const composer = await screen.findByPlaceholderText(/Type a message/);
    await user.type(composer, 'blocked draft');
    await user.click(screen.getByRole('button', { name: 'Send' }));

    const warning = await screen.findByRole('dialog', { name: 'This message is prohibited' });
    expect(warning).toHaveTextContent('contains inappropriate language');
    expect(composer).toHaveValue('blocked draft');
    expect(conversationApi.sendMessage).toHaveBeenCalledWith('42', { body: 'blocked draft', confirm_override: false });
    await user.click(screen.getByRole('button', { name: 'Edit message' }));
    await waitFor(() => expect(screen.queryByRole('dialog', { name: 'This message is prohibited' })).not.toBeInTheDocument());
  });

  it('shows the same warning for a blocked edit without replacing the original message', async () => {
    const user = userEvent.setup();
    conversationApi.listMessages.mockResolvedValue({ items: [{
      id: 7,
      body: 'Original message',
      is_mine: true,
      sender: { full_name: 'Demo Client', initials: 'DC' },
      created_at: '2026-11-01T00:00:00+00:00',
    }], meta: null });
    conversationApi.updateMessage.mockRejectedValue({
      status: 422,
      message: 'This edit was not saved because it contains inappropriate language. Please edit it and try again.',
    });
    renderThread();

    await user.click(await screen.findByRole('button', { name: 'Edit' }));
    const composer = screen.getByPlaceholderText(/Type a message/);
    await user.clear(composer);
    await user.type(composer, 'blocked edit');
    await user.click(screen.getByRole('button', { name: 'Save' }));

    const warning = await screen.findByRole('dialog', { name: 'This message is prohibited' });
    expect(warning).toHaveTextContent('This edit was not saved');
    expect(screen.getByText('Original message')).toBeInTheDocument();
    expect(composer).toHaveValue('blocked edit');
    expect(conversationApi.updateMessage).toHaveBeenCalledWith('42', 7, { body: 'blocked edit', confirm_override: false });
  });
});
