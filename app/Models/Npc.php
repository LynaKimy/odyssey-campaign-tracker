<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Non-player character linked to a campaign, created by the MJ
 *
 * @description Lightweight by default (name + description). Can optionally
 * reference a Monster for a full stat block via monster_id FK.
 *
 * @example
 * $npc->hasStatBlock(); // true if monster_id is set
 * $npc->monster->actions; // access stat block via Monster model
 */
class Npc extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'notes',
        'location',
        'monster_id',
        'avatar',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    public function hasStatBlock(): bool
    {
        return $this->monster_id !== null;
    }
}
