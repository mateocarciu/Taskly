<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('order');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_memberships')->withTimestamps();
    }
}
