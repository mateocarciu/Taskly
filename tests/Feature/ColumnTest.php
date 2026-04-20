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

describe('sequence update', function () {
    test('can move a column to a new position', function () {
        $first = Column::create(['team_id' => $this->team->id, 'name' => 'Todo', 'order' => 0]);
        $second = Column::create(['team_id' => $this->team->id, 'name' => 'Progress', 'order' => 1]);
        $third = Column::create(['team_id' => $this->team->id, 'name' => 'Done', 'order' => 2]);

        $this->actingAs($this->user)
            ->put(route('columns.sequence.update', $first), [
                'order' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('columns', ['id' => $second->id, 'order' => 0]);
        $this->assertDatabaseHas('columns', ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas('columns', ['id' => $third->id, 'order' => 2]);
    });

    test('cannot reorder a column from another team', function () {
        $foreignColumn = Column::create(['team_id' => $this->otherTeam->id, 'name' => 'Foreign', 'order' => 0]);

        $this->actingAs($this->user)
            ->put(route('columns.sequence.update', $foreignColumn), [
                'order' => 0,
            ])
            ->assertStatus(403);
    });

    test('reorders correctly when existing orders are 1-based', function () {
        $todo = Column::create(['team_id' => $this->team->id, 'name' => 'Todo', 'order' => 1]);
        $progress = Column::create(['team_id' => $this->team->id, 'name' => 'Progress', 'order' => 2]);
        $done = Column::create(['team_id' => $this->team->id, 'name' => 'Done', 'order' => 3]);

        $this->actingAs($this->user)
            ->put(route('columns.sequence.update', $todo), [
                'order' => 1,
            ])
            ->assertRedirect();

        $orderedIds = Column::query()
            ->where('team_id', $this->team->id)
            ->orderBy('order')
            ->pluck('id')
            ->all();

        expect($orderedIds)->toBe([$progress->id, $todo->id, $done->id]);
    });
});
