<?php

use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => null]);
    $this->userWithTeam = User::factory()->create(['team_id' => $this->team->id]);
    TeamMembership::create(['team_id' => $this->team->id, 'user_id' => $this->userWithTeam->id]);
});

test('redirects to the pending page if user has no team', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertRedirect(route('teams.pending'));
});

test('allows access if user has a team', function () {
    $this->actingAs($this->userWithTeam)
        ->get(route('dashboard'))
        ->assertOk();
});

test('sends privileged users to team management even without a team', function () {
    $admin = User::factory()->create(['team_id' => null, 'role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('teams.index'));

    $owner = User::factory()->create(['team_id' => null, 'role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertRedirect(route('teams.index'));
});

test('renders the pending page for users without a team', function () {
    $this->actingAs($this->user)
        ->get(route('teams.pending'))
        ->assertOk();
});
