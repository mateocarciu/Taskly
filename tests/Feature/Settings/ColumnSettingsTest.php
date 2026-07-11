<?php

use App\Models\Column;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    $this->otherTeam = Team::factory()->create();
});

test('unauthenticated users cannot access column settings', function () {
    $this->get('/settings/columns')
        ->assertRedirect('/login');
});

test('authenticated users can access column settings and see their columns', function () {
    $column = Column::create(['team_id' => $this->team->id, 'name' => 'To Do', 'type' => 'todo', 'order' => 0]);
    $otherColumn = Column::create(['team_id' => $this->otherTeam->id, 'name' => 'Other To Do', 'type' => 'todo', 'order' => 0]);

    $response = $this->actingAs($this->user)
        ->get(route('settings.columns.index'));

    $response->assertStatus(200);
    
    $response->assertInertia(fn ($page) => $page
        ->component('settings/Columns')
        ->has('columns', 1)
        ->where('columns.0.id', $column->id)
    );
});

test('can update column status category type', function () {
    $column = Column::create(['team_id' => $this->team->id, 'name' => 'In Progress', 'type' => 'in_progress', 'order' => 0]);

    $this->actingAs($this->user)
        ->put(route('columns.update', $column), [
            'name' => 'In Progress',
            'type' => 'done',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('columns', [
        'id' => $column->id,
        'type' => 'done',
    ]);
});

test('cannot update type to an invalid value', function () {
    $column = Column::create(['team_id' => $this->team->id, 'name' => 'In Progress', 'type' => 'in_progress', 'order' => 0]);

    $this->actingAs($this->user)
        ->put(route('columns.update', $column), [
            'name' => 'In Progress',
            'type' => 'invalid-type',
        ])
        ->assertSessionHasErrors(['type']);
});
