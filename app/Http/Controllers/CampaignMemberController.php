<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteCampaignMemberRequest;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages campaign membership (invite, remove, leave)
 *
 * @description Handles member operations for campaigns. Invite and remove
 * actions are protected by the campaign.role:mj middleware in routes.
 */
class CampaignMemberController extends Controller
{
    /**
     * Invite a registered user to the campaign
     */
    public function store(InviteCampaignMemberRequest $request, Campaign $campaign): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $campaign->members()->attach($user->id, [
            'role' => $request->validated('role'),
        ]);

        return back()->with('status', __('campaign.member_added'));
    }

    /**
     * Remove a member from the campaign (MJ action)
     */
    public function destroy(Campaign $campaign, User $user): RedirectResponse
    {
        // Prevent removing the campaign creator
        if ($user->id === $campaign->created_by) {
            return back()->with('error', __('campaign.cannot_remove_last_mj'));
        }

        // Prevent removing the last MJ
        if ($user->isMj($campaign) && $campaign->mjs()->count() <= 1) {
            return back()->with('error', __('campaign.cannot_remove_last_mj'));
        }

        $campaign->members()->detach($user->id);

        return back()->with('status', __('campaign.member_removed'));
    }

    /**
     * Leave a campaign (self-service for any member)
     */
    public function leave(Request $request, Campaign $campaign): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isMemberOf($campaign)) {
            abort(403);
        }

        // Prevent the last MJ from leaving
        if ($user->isMj($campaign) && $campaign->mjs()->count() <= 1) {
            return back()->with('error', __('campaign.cannot_remove_last_mj'));
        }

        $campaign->members()->detach($user->id);

        return redirect()
            ->route('campaigns.index')
            ->with('status', __('campaign.left_campaign'));
    }
}
