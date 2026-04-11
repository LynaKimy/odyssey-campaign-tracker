<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Campaign;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating game session instances
 *
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        $plannedAt = fake()->dateTimeBetween('-1 month', '+2 months');

        return [
            'campaign_id' => Campaign::factory(),
            'session_number' => fake()->numberBetween(1, 50),
            'title' => fake()->randomElement([
                'The Lost Temple',
                'Shadows Over Waterdeep',
                'The Dragon\'s Lair',
                'Into the Underdark',
                'The Final Confrontation',
                null,
            ]),
            'planned_at' => $plannedAt,
            'played_at' => null,
            'summary' => fake()->optional(0.5)->paragraph(),
            'gm_notes' => fake()->optional(0.3)->paragraph(),
            'in_game_date' => fake()->optional(0.4)->randomElement([
                '12 Mirtul, 1492 DR',
                '3 Flamerule, 1493 DR',
                '27 Eleint, 1491 DR',
            ]),
            'location' => fake()->optional(0.6)->randomElement([
                'Tavern', 'Castle', 'Dungeon', 'Forest', 'Temple', 'City Gates',
            ]),
            'status' => SessionStatus::Planned,
        ];
    }

    /**
     * Set session status to planned
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Planned,
            'played_at' => null,
        ]);
    }

    /**
     * Set session status to played with a played_at date
     */
    public function played(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Played,
            'played_at' => $attributes['planned_at'] ?? fake()->dateTimeBetween('-1 month', 'now'),
            'summary' => fake()->paragraph(),
        ]);
    }

    /**
     * Set session status to skipped
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Skipped,
            'played_at' => null,
        ]);
    }
}
