<?php

namespace App\Services;

use App\Models\CustomerMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CustomerMessageService
{
    public function getMessages(array $filters): Collection
    {
        $query = CustomerMessage::with(['user.customerProfile', 'staff']);

        if (!empty($filters['status'])) {
            $status = strtolower(trim((string) $filters['status']));
            if (in_array($status, ['ongoing', 'resolve'])) {
                $query->where('status', $status);
            }
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function createMessage(array $data, ?User $currentUser = null): CustomerMessage
    {
        $userId = $data['user_id'] ?? $currentUser?->id;
        if (! $userId) {
            $userId = User::first()?->id ?? 1;
        }

        $msg = CustomerMessage::create([
            'user_id' => $userId,
            'subject' => $data['subject'] ?? 'Product & Order Inquiry',
            'message' => $data['message'],
            'status' => $data['status'] ?? 'ongoing',
        ]);

        return $msg->load(['user.customerProfile', 'staff']);
    }

    public function replyToMessage(CustomerMessage $customerMessage, array $data): CustomerMessage
    {
        $status = $data['status'] ?? 'resolve';

        $customerMessage->update([
            'staff_reply' => $data['staff_reply'],
            'staff_id' => $data['staff_id'] ?? null,
            'status' => $status,
            'resolved_at' => $status === 'resolve' ? now() : null,
        ]);

        return $customerMessage->load(['user.customerProfile', 'staff']);
    }

    public function updateMessageStatus(CustomerMessage $customerMessage, string $status): CustomerMessage
    {
        $customerMessage->update([
            'status' => $status,
            'resolved_at' => $status === 'resolve' ? now() : null,
        ]);

        return $customerMessage->load(['user.customerProfile', 'staff']);
    }
}
