<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Character;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    protected $model = Character::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'name' => fake()->name(),
            'race' => fake()->randomElement(['Human', 'Elf', 'Dwarf', 'Halfling', 'Dragonborn', 'Tiefling']),
            'class' => fake()->randomElement(['Fighter', 'Wizard', 'Rogue', 'Cleric', 'Ranger', 'Paladin']),
            'level' => fake()->numberBetween(1, 20),
            'max_hp' => fake()->numberBetween(8, 200),
            'current_hp' => fn (array $attributes) => $attributes['max_hp'],
            'armor_class' => fake()->numberBetween(10, 22),
            'strength' => fake()->numberBetween(3, 20),
            'dexterity' => fake()->numberBetween(3, 20),
            'constitution' => fake()->numberBetween(3, 20),
            'intelligence' => fake()->numberBetween(3, 20),
            'wisdom' => fake()->numberBetween(3, 20),
            'charisma' => fake()->numberBetween(3, 20),
        ];
    }
}
