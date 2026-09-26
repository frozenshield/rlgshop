<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StorefrontChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiChatbotController extends Controller
{
    /**
     * Send a customer query to the storefront AI assistant (Aiko).
     */
    public function chat(Request $request, StorefrontChatbotService $service): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|min:1|max:1000',
            'history' => 'nullable|array',
            'history.*.role' => 'nullable|string',
            'history.*.content' => 'nullable|string',
        ]);

        try {
            $response = $service->reply(
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

    /**
     * Get starter quick prompts for the customer storefront chat widget.
     */
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
