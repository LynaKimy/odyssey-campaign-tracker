<?php

namespace App\Enums;

/**
 * Roles a user can hold within a specific campaign
 *
 * @description Backed string enum for campaign-scoped roles.
 * A user may hold different roles across different campaigns.
 *
 * @example
 * $user->roleInCampaign($campaign) === CampaignRole::GM;
 * CampaignRole::GM->label(); // "Maître du Jeu"
 */
enum CampaignRole: string
{
    case GM = 'gm';
    case Player = 'player';

    public function label(): string
    {
        return __("enums.campaign_role.{$this->value}");
    }
}
