<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChatRequest;
use App\Services\StorefrontChatbotService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AiChatbotController extends Controller
{
    public function __construct(
        protected StorefrontChatbotService $chatbotService
    ) {}

    public function chat(AiChatRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $response = $this->chatbotService->reply(
                trim($validated['message']),
                $validated['history'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $response,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An unexpected error occurred while communicating with the assistant.',
            ], 500);
        }
    }

    public function quickPrompts(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'label' => '🃏 Pokémon TCG 151 Stock',
                    'prompt' => 'Do you have the Pokémon Scarlet & Violet 151 Elite Trainer Box in stock and how much is it?',
                ],
                [
                    'label' => '📦 One Piece OP-05 Box',
                    'prompt' => 'How many boxes of One Piece Card Game OP-05 Awakening of the New Era do you have left?',
                ],
                [
                    'label' => '🎟️ Active Promo Codes',
                    'prompt' => 'What discount promo codes can I apply to my order today?',
                ],
                [
                    'label' => '🚚 Shipping & Free Delivery',
                    'prompt' => 'What are your delivery rates and how do I qualify for free shipping in the Philippines?',
                ],
                [
                    'label' => '🤖 Real Grade Gunpla Kits',
                    'prompt' => 'Show me your available authentic Bandai Gunpla model kits and their prices.',
                ],
            ],
        ]);
    }
}
