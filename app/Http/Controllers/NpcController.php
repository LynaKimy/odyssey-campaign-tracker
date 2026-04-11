<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNpcRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles NPC creation within a campaign (MJ only)
 *
 * @description Creates lightweight NPCs with name, description,
 * notes, and location. Monster stat block linking is not yet supported.
 */
class NpcController extends Controller
{
    public function create(Campaign $campaign): View
    {
        return view('npcs.create', compact('campaign'));
    }

    public function store(StoreNpcRequest $request, Campaign $campaign): RedirectResponse
    {
        $campaign->npcs()->create($request->validated());

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', __('character.npc_created'));
    }
}
