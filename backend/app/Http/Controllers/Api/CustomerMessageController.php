<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCustomerMessagesRequest;
use App\Http\Requests\ReplyCustomerMessageRequest;
use App\Http\Requests\StoreCustomerMessageRequest;
use App\Http\Requests\UpdateCustomerMessageStatusRequest;
use App\Models\CustomerMessage;
use App\Services\CustomerMessageService;
use Illuminate\Http\JsonResponse;

class CustomerMessageController extends Controller
{
    public function __construct(
        protected CustomerMessageService $customerMessageService
    ) {}

    public function index(GetCustomerMessagesRequest $request): JsonResponse
    {
        $messages = $this->customerMessageService->getMessages($request->validated());

        return response()->json([
            'success' => true,
            'count' => $messages->count(),
            'data' => $messages,
        ]);
    }

    public function store(StoreCustomerMessageRequest $request): JsonResponse
    {
        $msg = $this->customerMessageService->createMessage(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Message submitted successfully.',
            'data' => $msg,
        ], 201);
    }

    public function reply(ReplyCustomerMessageRequest $request, CustomerMessage $customerMessage): JsonResponse
    {
        $msg = $this->customerMessageService->replyToMessage($customerMessage, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully and message updated.',
            'data' => $msg,
        ]);
    }

    public function updateStatus(UpdateCustomerMessageStatusRequest $request, CustomerMessage $customerMessage): JsonResponse
    {
        $msg = $this->customerMessageService->updateMessageStatus($customerMessage, $request->validated('status'));

        return response()->json([
            'success' => true,
            'message' => 'Message status updated successfully.',
            'data' => $msg,
        ]);
    }
}
