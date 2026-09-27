<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyCustomerReviewRequest;
use App\Http\Requests\StoreCustomerReviewRequest;
use App\Models\CustomerReview;
use App\Services\CustomerReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    public function __construct(
        protected CustomerReviewService $customerReviewService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $reviews = $this->customerReviewService->getReviews($request->all());

        return response()->json([
            'success' => true,
            'count' => $reviews->count(),
            'data' => $reviews,
        ]);
    }

    public function store(StoreCustomerReviewRequest $request): JsonResponse
    {
        $review = $this->customerReviewService->createReview(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer review published successfully.',
            'data' => $review,
        ], 201);
    }

    public function reply(ReplyCustomerReviewRequest $request, CustomerReview $customer_review): JsonResponse
    {
        $review = $this->customerReviewService->replyToReview($customer_review, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff response sent to customer review.',
            'data' => $review,
        ]);
    }

    public function destroy(CustomerReview $customer_review): JsonResponse
    {
        $this->customerReviewService->deleteReview($customer_review);

        return response()->json([
            'success' => true,
            'message' => 'Customer review removed successfully.',
        ]);
    }
}
