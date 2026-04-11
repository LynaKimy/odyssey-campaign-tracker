<?php

namespace Tests\Feature;

use App\Enums\CampaignRole;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NpcCreationTest extends TestCase
{
    use RefreshDatabase;

    private function createCampaignWithMj(): array
    {
        $mj = User::factory()->create();
        $campaign = Campaign::factory()->create(['created_by' => $mj->id]);
        $campaign->members()->attach($mj->id, ['role' => CampaignRole::GM->value]);

        return [$campaign, $mj];
    }

    public function test_npc_create_screen_requires_authentication(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->get(route('campaigns.npcs.create', $campaign));

        $response->assertRedirect('/login');
    }

    public function test_npc_create_screen_requires_mj_role(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Player->value]);

        $response = $this->actingAs($joueur)->get(route('campaigns.npcs.create', $campaign));

        $response->assertForbidden();
    }

    public function test_mj_can_view_npc_create_form(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $response = $this->actingAs($mj)->get(route('campaigns.npcs.create', $campaign));

        $response->assertStatus(200);
    }

    public function test_mj_can_create_npc(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $response = $this->actingAs($mj)->post(route('campaigns.npcs.store', $campaign), [
            'name' => 'Elminster',
            'description' => 'A wise old wizard',
            'notes' => 'Ally of the party',
            'location' => 'Shadowdale',
        ]);

        $response->assertRedirect(route('campaigns.show', $campaign));

        $this->assertDatabaseHas('npcs', [
            'campaign_id' => $campaign->id,
            'name' => 'Elminster',
            'location' => 'Shadowdale',
        ]);
    }

    public function test_npc_creation_requires_name(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $response = $this->actingAs($mj)->post(route('campaigns.npcs.store', $campaign), []);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_non_member_cannot_create_npc(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->post(route('campaigns.npcs.store', $campaign), [
            'name' => 'Unauthorized NPC',
        ]);

        $response->assertForbidden();
    }
}
