<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefPokemonSet;
use App\Services\PokemonSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PokemonSetController extends Controller
{
    public function __construct(
        protected PokemonSetService $pokemonSetService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sets = $this->pokemonSetService->getSets($request->all());

        if (is_array($sets) && isset($sets['data'])) {
             return response()->json(array_merge(['success' => true], $sets));
        }

        return response()->json([
            'success' => true,
            'count' => count($sets),
            'data' => $sets,
        ]);
    }

    public function show(string $pokemon_set): JsonResponse
    {
        $set = $this->pokemonSetService->show($pokemon_set);

        if (!$set) {
            return response()->json([
                'success' => false,
                'message' => 'Pokemon set not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $set,
        ]);
    }

    public function series(): JsonResponse
    {
        $series = $this->pokemonSetService->getSeries();

        return response()->json([
            'success' => true,
            'data' => $series,
        ]);
    }
}
