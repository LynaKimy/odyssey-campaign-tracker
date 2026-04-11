<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Npc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Npc>
 */
class NpcFactory extends Factory
{
    protected $model = Npc::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'notes' => fake()->paragraph(),
            'location' => fake()->randomElement(['Tavern', 'Castle', 'Market', 'Forest', 'Temple']),
        ];
    }
}
