<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerReview;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    /**
     * Display a listing of customer product reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomerReview::with(['user.customerProfile', 'product', 'staff']);

        if ($request->filled('stars')) {
            $stars = (int) $request->input('stars');
            if ($stars >= 1 && $stars <= 5) {
                $query->where('stars', $stars);
            }
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->input('status')));
            if ($status === 'replied') {
                $query->whereNotNull('staff_reply');
            } elseif ($status === 'pending') {
                $query->whereNull('staff_reply');
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
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

        $reviews = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $reviews->count(),
            'data' => $reviews,
        ]);
    }

    /**
     * Store a new customer review.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'stars' => 'required|integer|min:1|max:5',
            'message' => 'required|string',
            'image' => 'nullable|string',
        ]);

        $userId = $validated['user_id'] ?? $request->user()?->id;
        if (! $userId) {
            $userId = User::first()?->id ?? 1;
        }

        $review = CustomerReview::create([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
            'stars' => $validated['stars'],
            'message' => $validated['message'],
            'image' => $validated['image'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer review published successfully.',
            'data' => $review->load(['user.customerProfile', 'product', 'staff']),
        ], 201);
    }

    /**
     * Staff reply to a customer review.
     */
    public function reply(Request $request, CustomerReview $customer_review): JsonResponse
    {
        $validated = $request->validate([
            'staff_reply' => 'required|string',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $staffId = $validated['staff_id'] ?? Staff::first()?->id ?? 1;

        $customer_review->update([
            'staff_reply' => $validated['staff_reply'],
            'staff_id' => $staffId,
            'replied_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff response sent to customer review.',
            'data' => $customer_review->load(['user.customerProfile', 'product', 'staff']),
        ]);
    }

    /**
     * Remove the specified customer review.
     */
    public function destroy(CustomerReview $customer_review): JsonResponse
    {
        $customer_review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer review removed successfully.',
        ]);
    }
}
