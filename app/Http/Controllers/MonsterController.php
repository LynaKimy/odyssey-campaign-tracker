<?php

namespace App\Http\Controllers;

use App\Models\Monster;
use Illuminate\Http\Request;

class MonsterController extends Controller
{
    public function index(Request $request)
    {
        $query = Monster::query();

        if ($search = $request->input('search')) {
            $query->whereTranslation('name', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($cr = $request->input('cr')) {
            $query->where('challenge_rating', $cr);
        }

        $monsters = $query->orderByTranslation('name')->paginate(24)->withQueryString();

        $types = Monster::distinct()->pluck('type')->sort()->values();
        $challengeRatings = Monster::distinct()->pluck('challenge_rating')->sort(SORT_NATURAL)->values();

        return view('monsters.index', compact('monsters', 'types', 'challengeRatings'));
    }

    public function show(Monster $monster)
    {
        return view('monsters.show', compact('monster'));
    }
}
