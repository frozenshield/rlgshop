<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerMessageController extends Controller
{
    /**
     * Display a listing of customer support messages/inquiries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomerMessage::with(['user.customerProfile', 'staff']);

        if ($request->filled('status')) {
            $status = strtolower(trim((string) $request->input('status')));
            if (in_array($status, ['ongoing', 'resolve'])) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'data' => $messages,
        ]);
    }

    /**
     * Store a new customer support message.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'status' => 'nullable|in:ongoing,resolve',
        ]);

        $userId = $validated['user_id'] ?? $request->user()?->id;
        if (! $userId) {
            // Default to first user if guest or demo
            $userId = User::first()?->id ?? 1;
        }

        $msg = CustomerMessage::create([
            'user_id' => $userId,
            'subject' => $validated['subject'] ?? 'Product & Order Inquiry',
            'message' => $validated['message'],
            'status' => $validated['status'] ?? 'ongoing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message submitted successfully.',
            'data' => $msg->load(['user.customerProfile', 'staff']),
        ], 201);
    }

    /**
     * Staff reply to a customer message and mark as resolved.
     */
    public function reply(Request $request, CustomerMessage $customerMessage): JsonResponse
    {
        $validated = $request->validate([
            'staff_reply' => 'required|string',
            'staff_id' => 'nullable|exists:staff,id',
            'status' => 'nullable|in:ongoing,resolve',
        ]);

        $customerMessage->update([
            'staff_reply' => $validated['staff_reply'],
            'staff_id' => $validated['staff_id'] ?? null,
            'status' => $validated['status'] ?? 'resolve',
            'resolved_at' => ($validated['status'] ?? 'resolve') === 'resolve' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully and message updated.',
            'data' => $customerMessage->load(['user.customerProfile', 'staff']),
        ]);
    }

    /**
     * Update the status of a customer message (ongoing / resolve).
     */
    public function updateStatus(Request $request, CustomerMessage $customerMessage): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:ongoing,resolve',
        ]);

        $customerMessage->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolve' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message status updated successfully.',
            'data' => $customerMessage->load(['user.customerProfile', 'staff']),
        ]);
    }
}
