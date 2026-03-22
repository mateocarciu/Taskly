<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    $this->otherTeam = Team::factory()->create();
});

describe('destroy', function () {
    test('cannot delete a column containing tasks', function () {
        $column = Column::create(['team_id' => $this->team->id, 'name' => 'To Do', 'order' => 1]);
        Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $column->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('columns.destroy', $column))
            ->assertSessionHasErrors('column');

        $this->assertDatabaseHas('columns', ['id' => $column->id]);
    });

    test('can delete an empty column', function () {
        $column = Column::create(['team_id' => $this->team->id, 'name' => 'To Do', 'order' => 1]);

        $this->actingAs($this->user)
            ->delete(route('columns.destroy', $column))
            ->assertRedirect();

        $this->assertDatabaseMissing('columns', ['id' => $column->id]);
    });
});
