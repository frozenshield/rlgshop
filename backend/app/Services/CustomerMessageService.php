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

        $currentUser = auth('sanctum')->user() ?? auth()->user();
        $authId = $currentUser?->id ?? auth('sanctum')->id() ?? auth()->id();

        // Staff and admin can view all inquiries or filter by user_id;
        // Regular customers and unauthenticated users are strictly scoped to auth()->id()
        if ($currentUser && in_array($currentUser->user_type, ['staff', 'admin'])) {
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }
        } elseif ($authId) {
            $query->where('user_id', $authId);
        } else {
            $query->whereRaw('1 = 0');
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $status = strtolower(trim((string) $filters['status']));
            if (in_array($status, ['ongoing', 'resolve'])) {
                $query->where('status', $status);
            }
        }

        if (! empty($filters['search'])) {
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
        $user = $currentUser ?? auth('sanctum')->user() ?? auth()->user();

        // Strictly enforce auth ID for regular customers to prevent user spoofing
        if ($user && ! in_array($user->user_type, ['staff', 'admin'])) {
            $userId = $user->id;
        } else {
            $userId = $data['user_id'] ?? $user?->id ?? User::first()?->id ?? 1;
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
