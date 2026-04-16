<?php

namespace App\Models;

use App\Enums\Monsters\MonsterSize;
use App\Enums\Monsters\MonsterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Reference data model for monsters, populated from SRD PDF parser.
 *
 * @description All data is stored in dedicated columns. Translatable fields
 * use spatie/laravel-translatable with JSON columns.
 * Non-translatable fields (stats, enums) are stored as plain columns.
 *
 * @property string                    $fingerprint
 * @property array                     $name
 * @property MonsterType               $type
 * @property MonsterSize               $size
 * @property string                    $alignment
 * @property array                     $desc
 * @property string                    $challenge_rating
 * @property float                     $cr
 * @property int                       $armor_class
 * @property int                       $hit_points
 * @property string                    $hit_dice
 * @property array                     $speed
 * @property int                       $strength
 * @property int                       $dexterity
 * @property int                       $constitution
 * @property int                       $intelligence
 * @property int                       $wisdom
 * @property int                       $charisma
 * @property array                     $saving_throws
 * @property array                     $traits
 * @property array                     $actions
 * @property array                     $legendary_actions
 * @property array                     $reactions
 * @property array                     $bonus_actions
 *
 * @example
 * Monster::where('type', MonsterType::Dragon)->get();
 * Monster::where('size', MonsterSize::Large)->get();
 * $monster->abilityModifier('strength');
 */
class Monster extends Model
{
    use Concerns\HasAbilityScores;
    use Concerns\HasTranslatableScopes;
    use HasFactory;
    use HasTranslations;

    public array $translatable = [
        'name',
        'desc',
        'traits',
        'actions',
        'legendary_actions',
        'reactions',
        'bonus_actions',
    ];

    protected $fillable = [
        'fingerprint',
        'name',
        'type',
        'size',
        'alignment',
        'desc',
        'challenge_rating',
        'cr',
        'armor_class',
        'hit_points',
        'hit_dice',
        'speed',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'saving_throws',
        'traits',
        'actions',
        'legendary_actions',
        'reactions',
        'bonus_actions',
    ];

    protected function casts(): array
    {
        return [
            'type'          => MonsterType::class,
            'size'          => MonsterSize::class,
            'speed'         => 'array',
            'saving_throws' => 'array',
            'traits'        => 'array',
            'actions'       => 'array',
            'legendary_actions' => 'array',
            'reactions'     => 'array',
            'bonus_actions' => 'array',
            'cr'            => 'decimal:2',
            'armor_class'   => 'integer',
            'hit_points'    => 'integer',
            'strength'      => 'integer',
            'dexterity'     => 'integer',
            'constitution'  => 'integer',
            'intelligence'  => 'integer',
            'wisdom'        => 'integer',
            'charisma'      => 'integer',
        ];
    }

    public function getFallbackLocale(): string
    {
        return 'en';
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    public function spells(): BelongsToMany
    {
        return $this->belongsToMany(Spell::class)->withTimestamps();
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeOfType(Builder $query, MonsterType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfSize(Builder $query, MonsterSize $size): Builder
    {
        return $query->where('size', $size);
    }

    public function scopeOfAlignment(Builder $query, string $alignment): Builder
    {
        return $query->where('alignment', $alignment);
    }

    public function scopeByCr(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('cr', [$min, $max]);
    }
}
