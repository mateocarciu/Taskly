<?php

use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => null]);
    $this->userWithTeam = User::factory()->create(['team_id' => $this->team->id]);
});

describe('middleware', function () {
    test('redirects to team selection if user has no team', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('teams.select'));
    });

    test('allows access if user has a team', function () {
        $this->actingAs($this->userWithTeam)
            ->get(route('dashboard'))
            ->assertOk();
    });
});

describe('team selection', function () {
    test('allows user to join a team', function () {
        $this->actingAs($this->user)
            ->post(route('teams.join'), ['team_id' => $this->team->id])
            ->assertRedirect(route('dashboard'));

        expect($this->user->fresh()->team_id)->toBe($this->team->id);
    });

    test('validates team_id is required', function () {
        $this->actingAs($this->user)
            ->post(route('teams.join'), [])
            ->assertSessionHasErrors(['team_id']);
    });

    test('redirects to dashboard if user already has a team', function () {
        $this->actingAs($this->userWithTeam)
            ->get(route('teams.select'))
            ->assertRedirect(route('dashboard'));
    });
});

