<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetConversationsRequest;
use App\Http\Requests\SendChatMessageRequest;
use App\Http\Requests\StartConversationRequest;
use App\Http\Requests\UpdateConversationStatusRequest;
use App\Models\Conversation;
use App\Services\ChatConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    public function __construct(
        protected ChatConversationService $chatConversationService
    ) {}

    /**
     * List conversations.
     */
    public function index(GetConversationsRequest $request): JsonResponse
    {
        $conversations = $this->chatConversationService->getConversations(
            $request->validated(),
            $request->user('sanctum') ?? $request->user()
        );

        return response()->json([
            'success' => true,
            'count' => $conversations->count(),
            'data' => $conversations,
        ]);
    }

    /**
     * Start or retrieve a conversation thread.
     */
    public function store(StartConversationRequest $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please sign in to start a chat.',
            ], 401);
        }

        $customerId = $user->user_type === 'customer'
            ? $user->id
            : ($request->validated('customer_id') ?? $user->id);

        $adminId = $request->validated('admin_id');

        $conversation = $this->chatConversationService->getOrCreateConversation(
            $customerId,
            $adminId
        );

        // If an initial message was included, send it
        $initialMessage = $request->validated('message');
        if (! empty($initialMessage)) {
            $this->chatConversationService->sendMessage($conversation, $initialMessage, $user);
        }

        return response()->json([
            'success' => true,
            'data' => $conversation->fresh(['customer.customerProfile', 'admin', 'latestMessage.sender']),
        ], 201);
    }

    /**
     * Show a single conversation room.
     */
    public function show(Conversation $conversation, Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        if ($user) {
            $isStaff = in_array($user->user_type, ['staff', 'admin']);
            $isParticipant = ($conversation->customer_id === $user->id) || ($conversation->admin_id === $user->id);
            if (! $isStaff && ! $isParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to conversation.',
                ], 403);
            }
        }

        $conversation->load(['customer.customerProfile', 'admin', 'latestMessage.sender']);
        if ($user) {
            $conversation->setAttribute('unread_count', $conversation->unreadCountFor($user->id));
        }

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ]);
    }

    /**
     * Get message history for a conversation and mark as read.
     */
    public function messages(Conversation $conversation, Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $messages = $this->chatConversationService->getMessages($conversation, $user);

        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'data' => $messages,
        ]);
    }

    /**
     * Send a message to the conversation.
     */
    public function sendMessage(SendChatMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $message = $this->chatConversationService->sendMessage(
            $conversation,
            $request->validated('content'),
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data' => $message,
        ], 201);
    }

    /**
     * Update conversation status (active, closed, resolved).
     */
    public function updateStatus(UpdateConversationStatusRequest $request, Conversation $conversation): JsonResponse
    {
        $updated = $this->chatConversationService->updateStatus(
            $conversation,
            $request->validated('status'),
            $request->validated('admin_id')
        );

        return response()->json([
            'success' => true,
            'message' => "Conversation status updated to {$request->validated('status')}.",
            'data' => $updated,
        ]);
    }
}
