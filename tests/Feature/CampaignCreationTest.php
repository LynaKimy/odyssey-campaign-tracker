<?php

namespace Tests\Feature;

use App\Enums\CampaignRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_campaign_screen_requires_authentication(): void
    {
        $response = $this->get('/campaigns/create');

        $response->assertRedirect('/login');
    }

    public function test_create_campaign_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/campaigns/create');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_create_campaign(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/campaigns', [
            'name' => 'The Lost Mines',
            'description' => 'A classic adventure',
            'system' => 'dnd5e-2024',
            'is_public' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('campaigns', [
            'name' => 'The Lost Mines',
            'created_by' => $user->id,
            'system' => 'dnd5e-2024',
            'is_public' => true,
        ]);
    }

    public function test_creator_is_automatically_assigned_mj_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/campaigns', [
            'name' => 'Test Campaign',
            'system' => 'dnd5e-2024',
        ]);

        $this->assertDatabaseHas('campaign_user', [
            'user_id' => $user->id,
            'role' => CampaignRole::GM->value,
        ]);
    }

    public function test_campaign_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/campaigns', []);

        $response->assertSessionHasErrors(['name', 'system']);
    }

    public function test_campaign_creation_validates_game_system(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/campaigns', [
            'name' => 'Test Campaign',
            'system' => 'invalid-system',
        ]);

        $response->assertSessionHasErrors(['system']);
    }
}
