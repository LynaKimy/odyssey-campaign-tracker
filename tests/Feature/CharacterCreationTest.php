<?php

namespace Tests\Feature;

use App\Enums\CampaignRole;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterCreationTest extends TestCase
{
    use RefreshDatabase;

    private function createCampaignWithMjAndJoueur(): array
    {
        $mj = User::factory()->create();
        $campaign = Campaign::factory()->create(['created_by' => $mj->id]);
        $campaign->members()->attach($mj->id, ['role' => CampaignRole::MJ->value]);

        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Joueur->value]);

        return [$campaign, $mj, $joueur];
    }

    public function test_character_create_screen_requires_authentication(): void
    {
        $campaign = Campaign::factory()->create();

        $response = $this->get(route('campaigns.characters.create', $campaign));

        $response->assertRedirect('/login');
    }

    public function test_character_create_screen_requires_campaign_membership(): void
    {
        [$campaign] = $this->createCampaignWithMjAndJoueur();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->get(route('campaigns.characters.create', $campaign));

        $response->assertForbidden();
    }

    public function test_mj_can_view_character_create_form(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMjAndJoueur();

        $response = $this->actingAs($mj)->get(route('campaigns.characters.create', $campaign));

        $response->assertStatus(200);
    }

    public function test_joueur_can_view_character_create_form(): void
    {
        [$campaign, , $joueur] = $this->createCampaignWithMjAndJoueur();

        $response = $this->actingAs($joueur)->get(route('campaigns.characters.create', $campaign));

        $response->assertStatus(200);
    }

    public function test_mj_can_create_character_for_any_member(): void
    {
        [$campaign, $mj, $joueur] = $this->createCampaignWithMjAndJoueur();

        $response = $this->actingAs($mj)->post(route('campaigns.characters.store', $campaign), [
            'user_id' => $joueur->id,
            'name' => 'Gandalf',
            'race' => 'Human',
            'class' => 'Wizard',
            'level' => 5,
            'max_hp' => 32,
            'armor_class' => 12,
            'strength' => 10,
            'dexterity' => 14,
            'constitution' => 12,
            'intelligence' => 20,
            'wisdom' => 16,
            'charisma' => 14,
        ]);

        $response->assertRedirect(route('campaigns.show', $campaign));

        $this->assertDatabaseHas('characters', [
            'campaign_id' => $campaign->id,
            'user_id' => $joueur->id,
            'name' => 'Gandalf',
        ]);
    }

    public function test_joueur_can_create_own_character(): void
    {
        [$campaign, , $joueur] = $this->createCampaignWithMjAndJoueur();

        $response = $this->actingAs($joueur)->post(route('campaigns.characters.store', $campaign), [
            'user_id' => $joueur->id,
            'name' => 'Legolas',
            'level' => 3,
        ]);

        $response->assertRedirect(route('campaigns.show', $campaign));

        $this->assertDatabaseHas('characters', [
            'campaign_id' => $campaign->id,
            'user_id' => $joueur->id,
            'name' => 'Legolas',
        ]);
    }

    public function test_joueur_cannot_create_character_for_another_user(): void
    {
        [$campaign, $mj, $joueur] = $this->createCampaignWithMjAndJoueur();

        $this->actingAs($joueur)->post(route('campaigns.characters.store', $campaign), [
            'user_id' => $mj->id,
            'name' => 'Stolen Character',
            'level' => 1,
        ]);

        // Character should be created for the joueur, not the MJ
        $this->assertDatabaseHas('characters', [
            'campaign_id' => $campaign->id,
            'user_id' => $joueur->id,
            'name' => 'Stolen Character',
        ]);

        $this->assertDatabaseMissing('characters', [
            'user_id' => $mj->id,
            'name' => 'Stolen Character',
        ]);
    }

    public function test_character_creation_requires_valid_data(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMjAndJoueur();

        $response = $this->actingAs($mj)->post(route('campaigns.characters.store', $campaign), []);

        $response->assertSessionHasErrors(['name', 'level', 'user_id']);
    }

    public function test_current_hp_is_set_to_max_hp_on_creation(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMjAndJoueur();

        $this->actingAs($mj)->post(route('campaigns.characters.store', $campaign), [
            'user_id' => $mj->id,
            'name' => 'Tank',
            'level' => 1,
            'max_hp' => 45,
        ]);

        $this->assertDatabaseHas('characters', [
            'name' => 'Tank',
            'max_hp' => 45,
            'current_hp' => 45,
        ]);
    }
}
