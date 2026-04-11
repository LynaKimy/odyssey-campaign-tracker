<?php

namespace App\Enums;

/**
 * Roles a user can hold within a specific campaign
 *
 * @description Backed string enum for campaign-scoped roles.
 * A user may hold different roles across different campaigns.
 *
 * @example
 * $user->roleInCampaign($campaign) === CampaignRole::MJ;
 * CampaignRole::MJ->label(); // "Maître du Jeu"
 */
enum CampaignRole: string
{
    case MJ = 'mj';
    case Joueur = 'joueur';

    public function label(): string
    {
        return __("enums.campaign_role.{$this->value}");
    }
}
