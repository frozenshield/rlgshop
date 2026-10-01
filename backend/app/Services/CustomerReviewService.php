<?php

namespace App\Services;

use App\Models\CustomerReview;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CustomerReviewService
{
    public function getReviews(array $filters): Collection
    {
        $query = CustomerReview::with(['user.customerProfile', 'product', 'staff']);

        if (! empty($filters['stars'])) {
            $stars = (int) $filters['stars'];
            if ($stars >= 1 && $stars <= 5) {
                $query->where('stars', $stars);
            }
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['status'])) {
            $status = strtolower(trim((string) $filters['status']));
            if ($status === 'replied') {
                $query->whereNotNull('staff_reply');
            } elseif ($status === 'pending') {
                $query->whereNull('staff_reply');
            }
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('staff_reply', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($pq) use ($search): void {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function createReview(array $data, ?User $currentUser = null): CustomerReview
    {
        $userId = $data['user_id'] ?? $currentUser?->id;
        if (! $userId) {
            $userId = User::first()?->id ?? 1;
        }

        $review = CustomerReview::create([
            'user_id' => $userId,
            'product_id' => $data['product_id'],
            'stars' => $data['stars'],
            'message' => $data['message'],
            'image' => $data['image'] ?? null,
        ]);

        return $review->load(['user.customerProfile', 'product', 'staff']);
    }

    public function replyToReview(CustomerReview $customerReview, array $data): CustomerReview
    {
        $staffId = $data['staff_id'] ?? Staff::first()?->id ?? 1;

        $customerReview->update([
            'staff_reply' => $data['staff_reply'],
            'staff_id' => $staffId,
            'replied_at' => now(),
        ]);

        return $customerReview->load(['user.customerProfile', 'product', 'staff']);
    }

    public function deleteReview(CustomerReview $customerReview): void
    {
        $customerReview->delete();
    }
}
