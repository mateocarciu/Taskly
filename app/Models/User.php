<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'team_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the team that owns the user.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the team memberships for the user.
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    /**
     * Get the teams that the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_memberships')->withTimestamps();
    }

    /**
     * Get the comments written by the user.
     */
    public function taskComments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Determine if the user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine if the user is privileged (owner or admin).
     */
    public function isPrivileged(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }

    /**
     * Determine if the user can access a given team.
     */
    public function canAccessTeam(Team|int $team): bool
    {
        if ($this->isPrivileged()) {
            return true;
        }

        return $this->teamMemberships()
            ->where('team_id', $team instanceof Team ? $team->getKey() : $team)
            ->exists();
    }

    /**
     * Determine if the user has an active team.
     */
    public function hasActiveTeam(): bool
    {
        return $this->team_id !== null && $this->canAccessTeam($this->team_id);
    }

    /**
     * Get a query builder for teams that the user can access.
     */
    public function accessibleTeamsQuery(): Builder
    {
        return $this->isPrivileged()
            ? Team::query()
            : Team::query()->whereHas('memberships', fn (Builder $query) => $query->where('user_id', $this->id));
    }

    public function scopeInTeam(Builder $query, int $teamId): Builder
    {
        return $query->where(function (Builder $q) use ($teamId) {
            $q->whereIn('role', ['owner', 'admin'])
                ->orWhereHas('teamMemberships', fn (Builder $membership) => $membership->where('team_id', $teamId));
        });
    }
}
