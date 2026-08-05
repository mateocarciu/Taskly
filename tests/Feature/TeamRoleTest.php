<?php

use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;

/**
 * Create a team and add $user as a member.
 */
function teamWithMember(User $user): Team
{
    $team = Team::factory()->create();
    TeamMembership::create(['team_id' => $team->id, 'user_id' => $user->id]);

    return $team;
}

// ─── Team creation ────────────────────────────────────────────────────────────

describe('team creation', function () {
    test('owner can create a team', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('teams.store'), ['name' => 'New Team'])
            ->assertRedirect(route('teams.index'));

        expect(Team::where('name', 'New Team')->exists())->toBeTrue();
    });

    test('admin can create a team', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('teams.store'), ['name' => 'Admin Team'])
            ->assertRedirect(route('teams.index'));

        expect(Team::where('name', 'Admin Team')->exists())->toBeTrue();
    });

    test('creating a team also creates its default columns', function () {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('teams.store'), ['name' => 'Default Columns Team'])
            ->assertRedirect(route('teams.index'));

        $team = Team::where('name', 'Default Columns Team')->firstOrFail();

        expect($team->columns()->orderBy('order')->pluck('type')->all())->toBe(['todo', 'in_progress', 'done']);
        expect($team->columns()->orderBy('order')->pluck('name')->all())->toBe(['To Do', 'In Progress', 'Done']);
    });

    test('member cannot create a team', function () {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->post(route('teams.store'), ['name' => 'Forbidden Team'])
            ->assertForbidden();
    });
});

// ─── Team rename ──────────────────────────────────────────────────────────────

describe('team rename', function () {
    test('owner can rename a team', function () {
        $owner = User::factory()->owner()->create();
        $team = Team::factory()->create(['name' => 'Old Name']);

        $this->actingAs($owner)
            ->patch(route('teams.update', $team), ['name' => 'New Name'])
            ->assertRedirect(route('teams.index'));

        expect($team->fresh()->name)->toBe('New Name');
    });

    test('admin can rename a team', function () {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)
            ->patch(route('teams.update', $team), ['name' => 'Renamed'])
            ->assertRedirect(route('teams.index'));

        expect($team->fresh()->name)->toBe('Renamed');
    });

    test('member cannot rename a team', function () {
        $member = User::factory()->create();
        $team = teamWithMember($member);

        $this->actingAs($member)
            ->patch(route('teams.update', $team), ['name' => 'Hacked Name'])
            ->assertForbidden();
    });
});

// ─── Team deletion ────────────────────────────────────────────────────────────

describe('team deletion', function () {
    test('owner can delete a team', function () {
        $owner = User::factory()->owner()->create();
        $team = Team::factory()->create();
        $owner->update(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->delete(route('teams.destroy', $team))
            ->assertRedirect(route('teams.index'));

        expect(Team::find($team->id))->toBeNull();
    });

    test('admin cannot delete a team', function () {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        $this->actingAs($admin)
            ->delete(route('teams.destroy', $team))
            ->assertForbidden();
    });

    test('member cannot delete a team', function () {
        $member = User::factory()->create();
        $team = teamWithMember($member);

        $this->actingAs($member)
            ->delete(route('teams.destroy', $team))
            ->assertForbidden();
    });
});

describe('add member', function () {
    test('owner can add a user to a team', function () {
        $owner = User::factory()->owner()->create();
        $team = Team::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('teams.members.store', $team), ['user_id' => $target->id])
            ->assertRedirect(route('teams.index'));

        expect(
            TeamMembership::where('team_id', $team->id)
                ->where('user_id', $target->id)
                ->exists()
        )->toBeTrue();

        expect($target->fresh()->team_id)->toBe($team->id);
    });

    test('admin can add a user to a team', function () {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('teams.members.store', $team), ['user_id' => $target->id])
            ->assertRedirect(route('teams.index'));

        expect(
            TeamMembership::where('team_id', $team->id)
                ->where('user_id', $target->id)
                ->exists()
        )->toBeTrue();
    });

    test('member cannot add a user to a team', function () {
        $member = User::factory()->create();
        $team = teamWithMember($member);
        $target = User::factory()->create();

        $this->actingAs($member)
            ->post(route('teams.members.store', $team), ['user_id' => $target->id])
            ->assertForbidden();
    });

    test('add member fails with non-existent user_id', function () {
        $owner = User::factory()->owner()->create();
        $team = Team::factory()->create();

        $this->actingAs($owner)
            ->post(route('teams.members.store', $team), ['user_id' => 99999])
            ->assertSessionHasErrors(['user_id']);
    });
});

// ─── Remove member ────────────────────────────────────────────────────────────

describe('remove member', function () {
    test('owner can remove a plain member', function () {
        $owner = User::factory()->owner()->create();
        $member = User::factory()->create();
        $team = teamWithMember($member);

        $this->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $member]))
            ->assertRedirect(route('teams.index'));

        expect(
            TeamMembership::where('team_id', $team->id)
                ->where('user_id', $member->id)
                ->exists()
        )->toBeFalse();
    });

    test('owner cannot remove an admin (they always have access)', function () {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $team = teamWithMember($admin);

        $this->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $admin]))
            ->assertForbidden();

        expect(
            TeamMembership::where('team_id', $team->id)
                ->where('user_id', $admin->id)
                ->exists()
        )->toBeTrue();
    });

    test('owner cannot remove themselves', function () {
        $owner = User::factory()->owner()->create();
        $team = teamWithMember($owner);

        $this->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $owner]))
            ->assertForbidden();
    });

    test('admin can remove a plain member', function () {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $team = teamWithMember($member);

        $this->actingAs($admin)
            ->delete(route('teams.members.destroy', [$team, $member]))
            ->assertRedirect(route('teams.index'));

        expect(
            TeamMembership::where('team_id', $team->id)
                ->where('user_id', $member->id)
                ->exists()
        )->toBeFalse();
    });

    test('admin cannot remove another admin', function () {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $team = teamWithMember($admin2);

        $this->actingAs($admin1)
            ->delete(route('teams.members.destroy', [$team, $admin2]))
            ->assertForbidden();
    });

    test('member cannot remove another member', function () {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $team = Team::factory()->create();
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $member1->id]);
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $member2->id]);

        $this->actingAs($member1)
            ->delete(route('teams.members.destroy', [$team, $member2]))
            ->assertForbidden();
    });
});

// ─── Promote ──────────────────────────────────────────────────────────────────

describe('promote', function () {
    test('owner can promote a member to admin', function () {
        $owner = User::factory()->owner()->create();
        $member = User::factory()->create(['role' => 'member']);
        $team = teamWithMember($member);

        $this->actingAs($owner)
            ->patch(route('teams.members.promote', [$team, $member]))
            ->assertRedirect(route('teams.index'));

        expect($member->fresh()->role)->toBe('admin');
    });

    test('admin can promote a member to admin', function () {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member']);
        $team = teamWithMember($member);

        $this->actingAs($admin)
            ->patch(route('teams.members.promote', [$team, $member]))
            ->assertRedirect(route('teams.index'));

        expect($member->fresh()->role)->toBe('admin');
    });

    test('cannot promote an already-admin user', function () {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $team = teamWithMember($admin);

        $this->actingAs($owner)
            ->patch(route('teams.members.promote', [$team, $admin]))
            ->assertForbidden();
    });

    test('member cannot promote anyone', function () {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $team = Team::factory()->create();
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $member1->id]);
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $member2->id]);

        $this->actingAs($member1)
            ->patch(route('teams.members.promote', [$team, $member2]))
            ->assertForbidden();
    });
});

// ─── Demote ───────────────────────────────────────────────────────────────────

describe('demote', function () {
    test('owner can demote an admin to member', function () {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $team = teamWithMember($admin);

        $this->actingAs($owner)
            ->patch(route('teams.members.demote', [$team, $admin]))
            ->assertRedirect(route('teams.index'));

        expect($admin->fresh()->role)->toBe('member');
    });

    test('demote keeps an admin without explicit membership in the team as a member', function () {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $owner->id]);

        $this->actingAs($owner)
            ->patch(route('teams.members.demote', [$team, $admin]))
            ->assertRedirect(route('teams.index'));

        $admin->refresh();

        expect($admin->role)->toBe('member');
        expect(TeamMembership::where('team_id', $team->id)->where('user_id', $admin->id)->exists())->toBeTrue();
        expect($admin->team_id)->toBe($team->id);
    });

    test('admin cannot demote another admin', function () {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $team = teamWithMember($admin2);

        $this->actingAs($admin1)
            ->patch(route('teams.members.demote', [$team, $admin2]))
            ->assertForbidden();
    });

    test('owner cannot demote themselves', function () {
        $owner = User::factory()->owner()->create();
        $team = teamWithMember($owner);

        $this->actingAs($owner)
            ->patch(route('teams.members.demote', [$team, $owner]))
            ->assertForbidden();
    });

    test('member cannot demote anyone', function () {
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $member->id]);
        TeamMembership::create(['team_id' => $team->id, 'user_id' => $admin->id]);

        $this->actingAs($member)
            ->patch(route('teams.members.demote', [$team, $admin]))
            ->assertForbidden();
    });
});

// ─── Team switch ──────────────────────────────────────────────────────────────

describe('team switch', function () {
    test('member can switch to a team they belong to', function () {
        $member = User::factory()->create();
        $team = teamWithMember($member);

        $this->actingAs($member)
            ->post(route('teams.switch', $team))
            ->assertRedirect();

        expect($member->fresh()->team_id)->toBe($team->id);
    });

    test('member cannot switch to a team they do not belong to', function () {
        $member = User::factory()->create();
        $other = Team::factory()->create();

        $this->actingAs($member)
            ->post(route('teams.switch', $other))
            ->assertForbidden();
    });

    test('owner can switch to any team', function () {
        $owner = User::factory()->owner()->create();
        $team = Team::factory()->create();

        $this->actingAs($owner)
            ->post(route('teams.switch', $team))
            ->assertRedirect();

        expect($owner->fresh()->team_id)->toBe($team->id);
    });
});
