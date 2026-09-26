<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefPokemonSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PokemonSetController extends Controller
{
    /**
     * Display a listing of Pokemon sets with optional filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RefPokemonSet::query();

        if ($request->filled('series')) {
            $query->bySeries($request->input('series'));
        }

        if ($request->filled('set_type')) {
            $query->where('set_type', $request->input('set_type'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('code')) {
            $code = trim($request->input('code'));
            $query->where('japanese_code', 'like', "%{$code}%");
        }

        $query->orderBy('release_order', 'asc');

        if ($request->boolean('paginate', false)) {
            $perPage = (int) $request->input('per_page', 25);
            $sets = $query->paginate($perPage);
        } else {
            $sets = $query->get();
        }

        return response()->json([
            'success' => true,
            'count' => is_countable($sets) ? count($sets) : $sets->total(),
            'data' => $sets,
        ]);
    }

    /**
     * Display the specified Pokemon set.
     */
    public function show(string $idOrCode): JsonResponse
    {
        $set = RefPokemonSet::where('id', $idOrCode)
            ->orWhere('japanese_code', $idOrCode)
            ->first();

        if (! $set) {
            return response()->json([
                'success' => false,
                'message' => "Pokémon set '{$idOrCode}' not found.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $set,
        ]);
    }

    /**
     * Get distinct series list with summary metadata.
     */
    public function series(): JsonResponse
    {
        $series = RefPokemonSet::select('series', 'series_years')
            ->selectRaw('count(*) as sets_count')
            ->selectRaw('min(release_order) as min_order')
            ->groupBy('series', 'series_years')
            ->orderBy('min_order', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $series,
        ]);
    }
}
