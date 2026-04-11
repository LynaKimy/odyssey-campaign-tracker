<?php

namespace Tests\Feature;

use App\Enums\CampaignRole;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignMemberTest extends TestCase
{
    use RefreshDatabase;

    private function createCampaignWithMj(): array
    {
        $mj = User::factory()->create();
        $campaign = Campaign::factory()->create(['created_by' => $mj->id]);
        $campaign->members()->attach($mj->id, ['role' => CampaignRole::MJ->value]);

        return [$campaign, $mj];
    }

    public function test_mj_can_invite_member_by_email(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $invitee = User::factory()->create();

        $response = $this->actingAs($mj)->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::Joueur->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $invitee->id,
            'role' => CampaignRole::Joueur->value,
        ]);
    }

    public function test_mj_can_invite_another_mj(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $invitee = User::factory()->create();

        $this->actingAs($mj)->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::MJ->value,
        ]);

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $invitee->id,
            'role' => CampaignRole::MJ->value,
        ]);
    }

    public function test_non_mj_cannot_invite_members(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Joueur->value]);
        $invitee = User::factory()->create();

        $response = $this->actingAs($joueur)->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::Joueur->value,
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_invite_members(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $invitee = User::factory()->create();

        $response = $this->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::Joueur->value,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_invite_requires_existing_email(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $response = $this->actingAs($mj)->post(route('campaigns.members.store', $campaign), [
            'email' => 'nonexistent@example.com',
            'role' => CampaignRole::Joueur->value,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_invite_rejects_already_member(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $existingMember = User::factory()->create();
        $campaign->members()->attach($existingMember->id, ['role' => CampaignRole::Joueur->value]);

        $response = $this->actingAs($mj)->post(route('campaigns.members.store', $campaign), [
            'email' => $existingMember->email,
            'role' => CampaignRole::Joueur->value,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_mj_can_remove_member(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Joueur->value]);

        $response = $this->actingAs($mj)->delete(route('campaigns.members.destroy', [$campaign, $joueur]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $joueur->id,
        ]);
    }

    public function test_mj_cannot_remove_campaign_creator(): void
    {
        [$campaign, $creator] = $this->createCampaignWithMj();
        $secondMj = User::factory()->create();
        $campaign->members()->attach($secondMj->id, ['role' => CampaignRole::MJ->value]);

        $response = $this->actingAs($secondMj)->delete(route('campaigns.members.destroy', [$campaign, $creator]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $creator->id,
        ]);
    }

    public function test_member_can_leave_campaign(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Joueur->value]);

        $response = $this->actingAs($joueur)->delete(route('campaigns.leave', $campaign));

        $response->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseMissing('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $joueur->id,
        ]);
    }

    public function test_last_mj_cannot_leave(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $response = $this->actingAs($mj)->delete(route('campaigns.leave', $campaign));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $mj->id,
        ]);
    }

    public function test_joueur_cannot_remove_other_members(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Joueur->value]);
        $otherJoueur = User::factory()->create();
        $campaign->members()->attach($otherJoueur->id, ['role' => CampaignRole::Joueur->value]);

        $response = $this->actingAs($joueur)->delete(route('campaigns.members.destroy', [$campaign, $otherJoueur]));

        $response->assertForbidden();
    }
}
