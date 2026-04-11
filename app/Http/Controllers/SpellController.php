<?php

namespace App\Http\Controllers;

use App\Models\Spell;
use Illuminate\Http\Request;

class SpellController extends Controller
{
    public function index(Request $request)
    {
        $query = Spell::query();

        if ($search = $request->input('search')) {
            $query->whereTranslation('name', "%{$search}%");
        }

        if ($request->filled('level')) {
            $query->where('level_int', $request->input('level'));
        }

        if ($school = $request->input('school')) {
            $query->where('school', $school);
        }

        $spells = $query->orderBy('level_int')->orderByTranslation('name')->paginate(24)->withQueryString();

        $schools = Spell::distinct()->pluck('school')->sort()->values();
        $levels = Spell::distinct()->pluck('level_int')->sort()->values();

        return view('spells.index', compact('spells', 'schools', 'levels'));
    }

    public function show(Spell $spell)
    {
        return view('spells.show', compact('spell'));
    }
}
