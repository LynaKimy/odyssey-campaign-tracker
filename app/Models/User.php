<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CampaignRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function campaignsAsMj(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->withPivot('role')
            ->wherePivot('role', CampaignRole::MJ->value)
            ->withTimestamps();
    }

    public function campaignsAsJoueur(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)
            ->withPivot('role')
            ->wherePivot('role', CampaignRole::Joueur->value)
            ->withTimestamps();
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get the user's role within a specific campaign
     *
     * @description Uses the already-loaded campaigns collection to avoid N+1.
     * Make sure to eager-load campaigns when checking roles in loops.
     */
    public function roleInCampaign(Campaign $campaign): ?CampaignRole
    {
        $membership = $this->campaigns->firstWhere('id', $campaign->id);

        return $membership ? CampaignRole::from($membership->pivot->role) : null;
    }

    public function isMj(Campaign $campaign): bool
    {
        return $this->roleInCampaign($campaign) === CampaignRole::MJ;
    }

    public function isJoueur(Campaign $campaign): bool
    {
        return $this->roleInCampaign($campaign) === CampaignRole::Joueur;
    }

    public function isMemberOf(Campaign $campaign): bool
    {
        return $this->roleInCampaign($campaign) !== null;
    }
}
