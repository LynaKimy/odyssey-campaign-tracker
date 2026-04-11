<?php

namespace Tests\Feature;

use App\Enums\CampaignRole;
use App\Enums\SessionStatus;
use App\Models\Campaign;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    private function createCampaignWithMj(): array
    {
        $mj = User::factory()->create();
        $campaign = Campaign::factory()->create(['created_by' => $mj->id]);
        $campaign->members()->attach($mj->id, ['role' => CampaignRole::MJ->value]);

        return [$campaign, $mj];
    }

    private function addJoueur(Campaign $campaign): User
    {
        $joueur = User::factory()->create();
        $campaign->members()->attach($joueur->id, ['role' => CampaignRole::Joueur->value]);

        return $joueur;
    }

    // ---------------------------------------------------------------
    // Model & Factory
    // ---------------------------------------------------------------

    public function test_session_can_be_created_with_factory(): void
    {
        [$campaign] = $this->createCampaignWithMj();

        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertDatabaseHas('game_sessions', [
            'id' => $session->id,
            'campaign_id' => $campaign->id,
        ]);
    }

    public function test_session_belongs_to_campaign(): void
    {
        [$campaign] = $this->createCampaignWithMj();

        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertInstanceOf(Campaign::class, $session->campaign);
        $this->assertTrue($session->campaign->is($campaign));
    }

    public function test_session_casts_status_to_enum(): void
    {
        $session = Session::factory()->create();

        $this->assertInstanceOf(SessionStatus::class, $session->status);
    }

    public function test_session_casts_planned_at_to_datetime(): void
    {
        $session = Session::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $session->planned_at);
    }

    public function test_session_casts_played_at_to_datetime(): void
    {
        $session = Session::factory()->played()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $session->played_at);
    }

    public function test_factory_planned_state(): void
    {
        $session = Session::factory()->planned()->create();

        $this->assertSame(SessionStatus::Planned, $session->status);
        $this->assertNull($session->played_at);
    }

    public function test_factory_played_state(): void
    {
        $session = Session::factory()->played()->create();

        $this->assertSame(SessionStatus::Played, $session->status);
        $this->assertNotNull($session->played_at);
        $this->assertNotNull($session->summary);
    }

    public function test_factory_skipped_state(): void
    {
        $session = Session::factory()->skipped()->create();

        $this->assertSame(SessionStatus::Skipped, $session->status);
        $this->assertNull($session->played_at);
    }

    public function test_session_uses_game_sessions_table(): void
    {
        $session = new Session();

        $this->assertSame('game_sessions', $session->getTable());
    }

    public function test_session_fillable_attributes(): void
    {
        $session = new Session();

        $expectedFillable = [
            'campaign_id',
            'session_number',
            'title',
            'planned_at',
            'played_at',
            'summary',
            'gm_notes',
            'in_game_date',
            'location',
            'status',
        ];

        $this->assertSame($expectedFillable, $session->getFillable());
    }

    // ---------------------------------------------------------------
    // SessionPolicy
    // ---------------------------------------------------------------

    public function test_member_can_view_any_sessions(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);

        $this->assertTrue($mj->can('viewAny', [Session::class, $campaign]));
        $this->assertTrue($joueur->can('viewAny', [Session::class, $campaign]));
    }

    public function test_non_member_cannot_view_any_sessions(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('viewAny', [Session::class, $campaign]));
    }

    public function test_member_can_view_session(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);

        $this->assertTrue($mj->can('view', [Session::class, $campaign]));
        $this->assertTrue($joueur->can('view', [Session::class, $campaign]));
    }

    public function test_non_member_cannot_view_session(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('view', [Session::class, $campaign]));
    }

    public function test_mj_can_create_session(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $this->assertTrue($mj->can('create', [Session::class, $campaign]));
    }

    public function test_joueur_cannot_create_session(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);

        $this->assertFalse($joueur->can('create', [Session::class, $campaign]));
    }

    public function test_non_member_cannot_create_session(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('create', [Session::class, $campaign]));
    }

    public function test_member_can_update_session(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertTrue($mj->can('update', $session));
        $this->assertTrue($joueur->can('update', $session));
    }

    public function test_non_member_cannot_update_session(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $outsider = User::factory()->create();
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertFalse($outsider->can('update', $session));
    }

    public function test_mj_can_delete_session(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertTrue($mj->can('delete', $session));
    }

    public function test_joueur_cannot_delete_session(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertFalse($joueur->can('delete', $session));
    }

    public function test_non_member_cannot_delete_session(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $outsider = User::factory()->create();
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertFalse($outsider->can('delete', $session));
    }

    public function test_nobody_can_restore_session(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertFalse($mj->can('restore', $session));
    }

    // ---------------------------------------------------------------
    // StoreSessionRequest authorization
    // ---------------------------------------------------------------

    public function test_store_request_authorizes_mj(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();

        $request = new \App\Http\Requests\StoreSessionRequest();
        $request->setUserResolver(fn () => $mj);
        $request->setRouteResolver(fn () => new class ($campaign) {
            public function __construct(private Campaign $campaign) {}

            public function parameter($name)
            {
                return $name === 'campaign' ? $this->campaign : null;
            }
        });

        $this->assertTrue($request->authorize());
    }

    public function test_store_request_denies_joueur(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);

        $request = new \App\Http\Requests\StoreSessionRequest();
        $request->setUserResolver(fn () => $joueur);
        $request->setRouteResolver(fn () => new class ($campaign) {
            public function __construct(private Campaign $campaign) {}

            public function parameter($name)
            {
                return $name === 'campaign' ? $this->campaign : null;
            }
        });

        $this->assertFalse($request->authorize());
    }

    // ---------------------------------------------------------------
    // UpdateSessionRequest rules
    // ---------------------------------------------------------------

    public function test_update_request_gm_gets_full_rules(): void
    {
        [$campaign, $mj] = $this->createCampaignWithMj();
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $request = new \App\Http\Requests\UpdateSessionRequest();
        $request->setUserResolver(fn () => $mj);
        $request->setRouteResolver(fn () => new class ($session) {
            public function __construct(private Session $session) {}

            public function parameter($name)
            {
                return $name === 'session' ? $this->session : null;
            }
        });

        $rules = $request->rules();

        $this->assertArrayHasKey('summary', $rules);
        $this->assertArrayHasKey('session_number', $rules);
        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('planned_at', $rules);
        $this->assertArrayHasKey('played_at', $rules);
        $this->assertArrayHasKey('gm_notes', $rules);
        $this->assertArrayHasKey('in_game_date', $rules);
        $this->assertArrayHasKey('location', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_update_request_joueur_gets_limited_rules(): void
    {
        [$campaign] = $this->createCampaignWithMj();
        $joueur = $this->addJoueur($campaign);
        $session = Session::factory()->create(['campaign_id' => $campaign->id]);

        $request = new \App\Http\Requests\UpdateSessionRequest();
        $request->setUserResolver(fn () => $joueur);
        $request->setRouteResolver(fn () => new class ($session) {
            public function __construct(private Session $session) {}

            public function parameter($name)
            {
                return $name === 'session' ? $this->session : null;
            }
        });

        $rules = $request->rules();

        $this->assertArrayHasKey('summary', $rules);
        $this->assertArrayNotHasKey('session_number', $rules);
        $this->assertArrayNotHasKey('title', $rules);
        $this->assertArrayNotHasKey('status', $rules);
    }
}
