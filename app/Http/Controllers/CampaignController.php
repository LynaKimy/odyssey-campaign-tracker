<?php

namespace App\Http\Controllers;

use App\Enums\CampaignRole;
use App\Enums\GameSystem;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = Campaign::where('is_public', true)
            ->orWhere('created_by', auth()->id())
            ->orWhereHas('members', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with('creator', 'members')
            ->latest()
            ->paginate(12);

        return view('campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('campaigns.create', [
            'gameSystems' => GameSystem::cases(),
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $campaign = Campaign::create([
            ...$request->safe()->except('is_public'),
            'is_public' => $request->boolean('is_public'),
            'created_by' => $request->user()->id,
        ]);

        $campaign->members()->attach($request->user()->id, [
            'role' => CampaignRole::MJ->value,
        ]);

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', __('campaign.campaign_created'));
    }

    public function show(Campaign $campaign): View
    {
        $campaign->load('creator', 'members', 'characters', 'npcs');

        // Eager-load campaigns for the authenticated user so that
        // isGM() / isMemberOf() calls in the view don't trigger N+1
        auth()->user()?->load('campaigns');

        return view('campaigns.show', compact('campaign'));
    }
}
