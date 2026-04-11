<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Session;
use Illuminate\Database\Seeder;

class SessionSeeder extends Seeder
{
    /**
     * Seed game sessions for each existing campaign
     *
     * @description Creates a realistic timeline of sessions per campaign:
     * played sessions first, then one planned upcoming session.
     */
    public function run(): void
    {
        $campaigns = Campaign::all();

        foreach ($campaigns as $campaign) {
            // Create 3 played sessions in the past
            Session::factory()
                ->count(3)
                ->played()
                ->sequence(
                    fn (int $index) => [
                        'campaign_id' => $campaign->id,
                        'session_number' => $index + 1,
                        'planned_at' => now()->subWeeks(3 - $index),
                    ],
                )
                ->create();

            // Create 1 upcoming planned session
            Session::factory()
                ->planned()
                ->create([
                    'campaign_id' => $campaign->id,
                    'session_number' => 4,
                    'planned_at' => now()->addWeek(),
                ]);
        }
    }
}
