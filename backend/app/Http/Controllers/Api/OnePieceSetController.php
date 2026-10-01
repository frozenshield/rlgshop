<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnePieceSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnePieceSetController extends Controller
{
    public function __construct(
        protected OnePieceSetService $onePieceSetService
    ) {}

    /**
     * Display a listing of One Piece sets.
     */
    public function index(Request $request): JsonResponse
    {
        $sets = $this->onePieceSetService->getSets($request->all());

        return response()->json([
            'success' => true,
            'count' => $sets->count(),
            'data' => $sets,
        ]);
    }

    /**
     * Display the specified One Piece set.
     */
    public function show(string $onepiece_set): JsonResponse
    {
        $set = $this->onePieceSetService->show($onepiece_set);

        if (! $set) {
            return response()->json([
                'success' => false,
                'message' => 'One Piece set not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $set,
        ]);
    }

    /**
     * List distinct One Piece product lines.
     */
    public function productLines(): JsonResponse
    {
        $lines = $this->onePieceSetService->getProductLines();

        return response()->json([
            'success' => true,
            'data' => $lines,
        ]);
    }
}
