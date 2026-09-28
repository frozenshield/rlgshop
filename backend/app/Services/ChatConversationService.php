<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatConversationService
{
    /**
     * Get conversations based on user role and filters.
     * Customers only see their own conversation; staff/admins see all.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Conversation>
     */
    public function getConversations(array $filters, ?User $currentUser = null): Collection
    {
        $user = $currentUser ?? auth('sanctum')->user() ?? auth()->user();

        if (! $user) {
            return new Collection;
        }

        $query = Conversation::with([
            'customer.customerProfile',
            'admin',
            'latestMessage.sender',
        ]);

        $isStaff = in_array($user->user_type, ['staff', 'admin']);

        if (! $isStaff) {
            // Strictly scope to the buyer's ID
            $query->where('customer_id', $user->id);
        } else {
            if (! empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', strtolower(trim((string) $filters['status'])));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->whereHas('customer', function ($cq) use ($search): void {
                    $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('messages', function ($mq) use ($search): void {
                    $mq->where('content', 'like', "%{$search}%");
                });
            });
        }

        $conversations = $query->orderBy('updated_at', 'desc')->get();

        // Attach unread count for the current user
        $conversations->each(function (Conversation $c) use ($user): void {
            $c->setAttribute('unread_count', $c->unreadCountFor($user->id));
        });

        return $conversations;
    }

    /**
     * Find or initiate a conversation room for a customer.
     */
    public function getOrCreateConversation(int $customerId, ?int $adminId = null): Conversation
    {
        $conversation = Conversation::where('customer_id', $customerId)
            ->whereIn('status', ['active', 'resolved'])
            ->latest('updated_at')
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'customer_id' => $customerId,
                'admin_id' => $adminId,
                'status' => 'active',
            ]);
        } elseif ($adminId && ! $conversation->admin_id) {
            $conversation->update(['admin_id' => $adminId]);
        }

        return $conversation->load(['customer.customerProfile', 'admin', 'latestMessage.sender']);
    }

    /**
     * Retrieve all messages in a conversation and mark unread incoming messages as read.
     *
     * @return Collection<int, Message>
     */
    public function getMessages(Conversation $conversation, ?User $currentUser = null): Collection
    {
        $user = $currentUser ?? auth('sanctum')->user() ?? auth()->user();

        if ($user) {
            $isStaff = in_array($user->user_type, ['staff', 'admin']);
            $isParticipant = ($conversation->customer_id === $user->id) || ($conversation->admin_id === $user->id);

            if (! $isStaff && ! $isParticipant) {
                abort(403, 'Unauthorized access to conversation.');
            }

            // Mark messages sent by the other party as read
            Message::where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return $conversation->messages()->with('sender')->get();
    }

    /**
     * Send a new chat message into the conversation room.
     */
    public function sendMessage(Conversation $conversation, string $content, ?User $currentUser = null): Message
    {
        $user = $currentUser ?? auth('sanctum')->user() ?? auth()->user();

        if (! $user) {
            abort(401, 'Unauthenticated to send messages.');
        }

        $isStaff = in_array($user->user_type, ['staff', 'admin']);
        $isParticipant = ($conversation->customer_id === $user->id) || ($conversation->admin_id === $user->id);

        if (! $isStaff && ! $isParticipant) {
            abort(403, 'Unauthorized to message in this conversation.');
        }

        // Auto-assign staff admin if not assigned yet
        if ($isStaff && ! $conversation->admin_id) {
            $conversation->admin_id = $user->id;
        }

        // Auto-reopen if customer sends message to a closed or resolved conversation
        if (! $isStaff && in_array($conversation->status, ['closed', 'resolved'])) {
            $conversation->status = 'active';
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $content,
            'is_read' => false,
        ]);

        // Touch parent conversation updated_at for sorting active chats
        $conversation->touch();
        $conversation->save();

        return $message->load('sender');
    }

    /**
     * Update conversation status and optionally assign admin.
     */
    public function updateStatus(Conversation $conversation, string $status, ?int $adminId = null): Conversation
    {
        $updates = ['status' => $status];
        if ($adminId) {
            $updates['admin_id'] = $adminId;
        }

        $conversation->update($updates);

        return $conversation->load(['customer.customerProfile', 'admin', 'latestMessage.sender']);
    }
}
