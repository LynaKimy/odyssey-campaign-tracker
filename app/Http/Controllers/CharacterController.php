<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCharacterRequest;
use App\Models\Campaign;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles character creation and display within a campaign
 *
 * @description MJs can create characters for any campaign member and view all.
 * Joueurs can only create their own characters and view them.
 */
class CharacterController extends Controller
{
    public function create(Campaign $campaign): View
    {
        $campaign->load('members');

        return view('characters.create', [
            'campaign' => $campaign,
            'members' => $campaign->members,
            'isGM' => auth()->user()->isGM($campaign),
        ]);
    }

    public function store(StoreCharacterRequest $request, Campaign $campaign): RedirectResponse
    {
        // Joueurs can only create characters for themselves
        $userId = $request->user()->isGM($campaign)
            ? $request->validated('user_id')
            : $request->user()->id;

        $data = $request->safe()->except(['user_id', 'avatar']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $campaign->characters()->create([
            ...$data,
            'user_id' => $userId,
            'current_hp' => $request->validated('max_hp'),
        ]);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', __('character.character_created'));
    }

    public function show(Campaign $campaign, Character $character): View
    {
        // Ensure the character belongs to this campaign
        if ($character->campaign_id !== $campaign->id) {
            abort(404);
        }

        // Only the owner or a campaign MJ can view the character details
        $user = auth()->user();

        if ($character->user_id !== $user->id && ! $user->isGM($campaign)) {
            abort(403);
        }

        $character->load('user', 'spells');

        return view('characters.show', compact('campaign', 'character'));
    }
}
