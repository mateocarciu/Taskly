<?php

namespace App\Services;

use App\Models\Column;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TeamService
{
    public function create(string $name, User $creator): Team
    {
        $team = Team::create(['name' => $name]);

        TeamMembership::create([
            'team_id' => $team->id,
            'user_id' => $creator->id,
        ]);

        $creator->update(['team_id' => $team->id]);

        $this->createDefaultColumns($team);

        return $team;
    }

    private function createDefaultColumns(Team $team): void
    {
        $columns = [
            ['name' => 'To Do', 'type' => 'todo'],
            ['name' => 'In Progress', 'type' => 'in_progress'],
            ['name' => 'Done', 'type' => 'done'],
        ];

        foreach ($columns as $index => $column) {
            Column::create([
                'team_id' => $team->id,
                'name' => $column['name'],
                'type' => $column['type'],
                'order' => $index + 1,
            ]);
        }
    }

    public function rename(Team $team, string $name): bool
    {
        return $team->update(['name' => $name]);
    }

    public function delete(Team $team): void
    {
        User::query()->where('team_id', $team->id)->update(['team_id' => null]);

        $team->delete();
    }

    public function setActiveTeam(User $user, Team|int $team): void
    {
        $user->update([
            'team_id' => $team instanceof Team ? $team->getKey() : $team,
        ]);
    }

    /**
     * Get the user's current team with its memberships, if any.
     */
    public function currentTeam(User $user): ?Team
    {
        if ($user->team_id === null) {
            return null;
        }

        return $user->team()->with('memberships.user')->first();
    }

    /**
     * Add a user to a team. Accepts either a User instance or a user id.
     */
    public function addMember(Team $team, User|int $user): User
    {
        $member = $user instanceof User ? $user : User::query()->findOrFail($user);

        TeamMembership::firstOrCreate([
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);

        if ($member->team_id === null) {
            $member->update(['team_id' => $team->id]);
        }

        return $member;
    }

    public function removeMember(Team $team, User $user): void
    {
        TeamMembership::query()
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($user->team_id === $team->id) {
            $user->update(['team_id' => null]);
        }
    }

    public function promoteToAdmin(User $user): void
    {
        $user->update(['role' => 'admin']);
    }

    public function demoteToMember(User $user, ?Team $team = null): void
    {
        if ($team !== null) {
            TeamMembership::firstOrCreate([
                'team_id' => $team->id,
                'user_id' => $user->id,
            ]);
        }

        $user->update(['role' => 'member']);

        if ($team !== null && $user->team_id === null) {
            $user->update(['team_id' => $team->id]);
        }
    }

    public function listForSettings(User $user): Collection
    {
        return $user->accessibleTeamsQuery()
            ->withCount('users')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Privileged users implicitly belong to every team, so they are always
     * listed even when they have no explicit membership.
     *
     * @return array<int, array{id: int, name: string, email: string, role: string}>
     */
    public function members(Team $team): array
    {
        $fromMemberships = $team->memberships->map(fn (TeamMembership $membership) => [
            'id' => $membership->user->id,
            'name' => $membership->user->name,
            'email' => $membership->user->email,
            'role' => $membership->user->role,
        ]);

        $privileged = User::query()
            ->whereIn('role', ['owner', 'admin'])
            ->whereNotIn('id', $fromMemberships->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);

        return $fromMemberships->concat($privileged)->sortBy('name')->values()->all();
    }

    /**
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function availableUsers(Team $team): array
    {
        $memberIds = collect($this->members($team))->pluck('id');

        return User::query()
            ->whereNotIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }
}
