<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    /** @use HasFactory<\Database\Factories\SessionFactory> */
    use HasFactory;

    protected $table = 'game_sessions';

    protected $fillable = [
        'campaign_id',
        'session_number',
        'title',
        'planned_at',
        'played_at',
        'summary',
        'gm_notes',
        'in_game_date',
        'location',
        'status'
    ];

    protected $casts = [
        'planned_at' => 'datetime',
        'played_at' => 'datetime',
        'status' => SessionStatus::class,
    ];

    public function campaign() : BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
