<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

/**
 * Authorization policy for campaign operations
 *
 * @description Admins bypass all checks via before(). Guest access is
 * supported for public campaigns through nullable User in view().
 */
class CampaignPolicy
{
    /**
     * Admin users can perform any action
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Members can view their campaigns. Guests can view public campaigns.
     */
    public function view(?User $user, Campaign $campaign): bool
    {
        if ($campaign->is_public) {
            return true;
        }

        return $user !== null && $user->isMemberOf($campaign);
    }

    /**
     * Any authenticated user can create a campaign (they become MJ)
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->isGM($campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isGM($campaign);
    }

    public function invite(User $user, Campaign $campaign): bool
    {
        return $user->isGM($campaign);
    }

    public function removePlayer(User $user, Campaign $campaign): bool
    {
        return $user->isGM($campaign);
    }

    /**
     * Only joueurs can leave. MJs must transfer ownership or delete the campaign.
     */
    public function leave(User $user, Campaign $campaign): bool
    {
        return $user->isPlayer($campaign);
    }
}
