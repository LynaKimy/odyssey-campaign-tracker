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

    private function createCampaignWithGM(): array
    {
        $gm = User::factory()->create();
        $campaign = Campaign::factory()->create(['created_by' => $gm->id]);
        $campaign->members()->attach($gm->id, ['role' => CampaignRole::GM->value]);

        return [$campaign, $gm];
    }

    public function test_GM_can_invite_member_by_email(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $invitee = User::factory()->create();

        $response = $this->actingAs($gm)->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::Player->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $invitee->id,
            'role' => CampaignRole::Player->value,
        ]);
    }

    public function test_GM_can_invite_another_GM(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $invitee = User::factory()->create();

        $this->actingAs($gm)->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::GM->value,
        ]);

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $invitee->id,
            'role' => CampaignRole::GM->value,
        ]);
    }

    public function test_non_GM_cannot_invite_members(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $Player = User::factory()->create();
        $campaign->members()->attach($Player->id, ['role' => CampaignRole::Player->value]);
        $invitee = User::factory()->create();

        $response = $this->actingAs($Player)->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::Player->value,
        ]);

        $response->assertForbidden();
    }

    public function test_guest_cannot_invite_members(): void
    {
        [$campaign] = $this->createCampaignWithGM();
        $invitee = User::factory()->create();

        $response = $this->post(route('campaigns.members.store', $campaign), [
            'email' => $invitee->email,
            'role' => CampaignRole::Player->value,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_invite_requires_existing_email(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();

        $response = $this->actingAs($gm)->post(route('campaigns.members.store', $campaign), [
            'email' => 'nonexistent@example.com',
            'role' => CampaignRole::Player->value,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_invite_rejects_already_member(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $existingMember = User::factory()->create();
        $campaign->members()->attach($existingMember->id, ['role' => CampaignRole::Player->value]);

        $response = $this->actingAs($gm)->post(route('campaigns.members.store', $campaign), [
            'email' => $existingMember->email,
            'role' => CampaignRole::Player->value,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_GM_can_remove_member(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $Player = User::factory()->create();
        $campaign->members()->attach($Player->id, ['role' => CampaignRole::Player->value]);

        $response = $this->actingAs($gm)->delete(route('campaigns.members.destroy', [$campaign, $Player]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $Player->id,
        ]);
    }

    public function test_GM_cannot_remove_campaign_creator(): void
    {
        [$campaign, $creator] = $this->createCampaignWithGM();
        $secondGM = User::factory()->create();
        $campaign->members()->attach($secondGM->id, ['role' => CampaignRole::GM->value]);

        $response = $this->actingAs($secondGM)->delete(route('campaigns.members.destroy', [$campaign, $creator]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $creator->id,
        ]);
    }

    public function test_member_can_leave_campaign(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $Player = User::factory()->create();
        $campaign->members()->attach($Player->id, ['role' => CampaignRole::Player->value]);

        $response = $this->actingAs($Player)->delete(route('campaigns.leave', $campaign));

        $response->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseMissing('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $Player->id,
        ]);
    }

    public function test_last_GM_cannot_leave(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();

        $response = $this->actingAs($gm)->delete(route('campaigns.leave', $campaign));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('campaign_user', [
            'campaign_id' => $campaign->id,
            'user_id' => $gm->id,
        ]);
    }

    public function test_Player_cannot_remove_other_members(): void
    {
        [$campaign, $gm] = $this->createCampaignWithGM();
        $Player = User::factory()->create();
        $campaign->members()->attach($Player->id, ['role' => CampaignRole::Player->value]);
        $otherPlayer = User::factory()->create();
        $campaign->members()->attach($otherPlayer->id, ['role' => CampaignRole::Player->value]);

        $response = $this->actingAs($Player)->delete(route('campaigns.members.destroy', [$campaign, $otherPlayer]));

        $response->assertForbidden();
    }
}
