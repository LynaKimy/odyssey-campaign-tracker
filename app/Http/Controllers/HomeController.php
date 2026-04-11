<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Monster;
use App\Models\Spell;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'campaignCount' => Campaign::count(),
            'monsterCount' => Monster::count(),
            'spellCount' => Spell::count(),
            'publicCampaigns' => Campaign::where('is_public', true)->latest()->take(5)->get(),
        ]);
    }
}
