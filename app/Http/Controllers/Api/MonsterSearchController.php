<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonsterSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $monsters = Monster::whereTranslation('name', "%{$query}%")
            ->orderByTranslation('name')
            ->limit(10)
            ->get(['id', 'name', 'challenge_rating', 'armor_class', 'hit_points', 'type', 'size']);

        return response()->json($monsters->map(fn (Monster $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'challenge_rating' => $m->challenge_rating,
            'armor_class' => $m->armor_class,
            'hit_points' => $m->hit_points,
            'type' => $m->type,
            'size' => $m->size,
        ]));
    }
}
