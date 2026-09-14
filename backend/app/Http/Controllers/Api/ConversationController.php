<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\SendMessageRequest;
use App\Http\Requests\Conversation\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessagingService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Not audience-namespaced: a client and a provider act on the same
 * `conversations` resource, scoped entirely by ConversationPolicy - same
 * shape as Phase 5's BookingController.
 */
class ConversationController extends Controller
{
    public function __construct(private readonly MessagingService $messaging) {}

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $conversation = $this->messaging->startOrGetConversation($request->user(), $request->validated());

        $conversation->load(['clientUser', 'providerUser']);

        return ApiResponse::ok(new ConversationResource($conversation));
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('provider_user_id', $user->id))
            ->with(['clientUser', 'providerUser', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        return ApiResponse::ok(ConversationResource::collection($conversations));
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $perPage = min($request->integer('per_page', config('webis.pagination.default')), config('webis.pagination.max'));

        // reorder(), not orderByDesc(): the messages() relation already
        // carries orderBy('id'), and stacking a second ORDER BY produces
        // `ORDER BY id ASC, id DESC` - ascending wins, so page 1 was the
        // OLDEST 15 messages and anything newer than that never rendered.
        // Only visible once a thread passes per_page messages.
        $messages = $conversation->messages()
            ->with('sender')
            ->reorder('id', 'desc')
            ->paginate($perPage);

        return ApiResponse::paginated($messages, MessageResource::class);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $result = $this->messaging->send(
            $conversation,
            $request->user(),
            $request->validated('body'),
            (bool) $request->validated('confirm_override', false),
        );

        if ($result['status'] === 'requires_confirmation') {
            return ApiResponse::ok([
                'requires_confirmation' => true,
                'message' => 'This looks like it may contain contact information — edit or send anyway?',
            ]);
        }

        return ApiResponse::created(new MessageResource($result['message']->load('sender')), 'Message sent.');
    }

    /**
     * PATCH /conversations/{conversation}/messages/{message} - sender edits.
     * The nested binding is scoped (`scopeBindings()` on the route), so a
     * message id from another thread 404s instead of leaking.
     */
    public function updateMessage(SendMessageRequest $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorize('view', $conversation);
        $this->authorize('update', $message);

        $result = $this->messaging->edit(
            $message,
            $request->user(),
            $request->validated('body'),
            (bool) $request->validated('confirm_override', false),
        );

        if ($result['status'] === 'requires_confirmation') {
            return ApiResponse::ok([
                'requires_confirmation' => true,
                'message' => 'This looks like it may contain contact information — edit or save anyway?',
            ]);
        }

        return ApiResponse::ok(new MessageResource($result['message']->load('sender')), 'Message updated.');
    }

    /**
     * DELETE /conversations/{conversation}/messages/{message} - sender unsends.
     */
    public function destroyMessage(Request $request, Conversation $conversation, Message $message): JsonResponse
    {
        $this->authorize('view', $conversation);
        $this->authorize('delete', $message);

        $this->messaging->unsend($message, $request->user());

        return ApiResponse::noContent('Message deleted.');
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->messaging->markRead($conversation, $request->user());

        return ApiResponse::ok(null, 'Marked as read.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::ok(['count' => $this->messaging->unreadCountFor($request->user())]);
    }
}
