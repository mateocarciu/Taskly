<?php

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\Column;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    $this->otherTeam = Team::factory()->create();
});

describe('index', function () {
    test('require auth', function () {
        $this->get(route('tasks.index'))
            ->assertRedirect(route('login'));
    });

    test('display tasks for the users team', function () {
        $column = Column::create(['team_id' => $this->team->id, 'name' => 'To Do', 'order' => 1]);
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $column->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(
                function ($page) use ($column, $task) {
                    return $page
                        ->component('Tasks')
                        ->has('columns', 1)
                        ->where('columns.0.id', $column->id)
                        ->has('columns.0.tasks', 1)
                        ->where('columns.0.tasks.0.id', $task->id);
                }
            );
    });

    test('loads creator relationship with tasks', function () {
        $column = Column::create(['team_id' => $this->team->id, 'name' => 'To Do', 'order' => 1]);
        $creator = User::factory()->create(['team_id' => $this->team->id]);
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $column->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(
                function ($page) use ($column, $creator) {
                    return $page
                        ->component('Tasks')
                        ->has('columns', 1)
                        ->has('columns.0.tasks', 1)
                        ->where('columns.0.tasks.0.creator.id', $creator->id)
                        ->where('columns.0.tasks.0.creator.name', $creator->name);
                }
            );
    });
});

describe('store', function () {
    beforeEach(function () {
        Column::create([
            'team_id' => $this->team->id,
            'name' => 'To Do',
            'order' => 1,
        ]);
    });

    test('creates a task for the user team', function () {
        $taskData = [
            'title' => 'New task',
            'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ];

        $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData)
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'New task',
            'team_id' => $this->team->id,
        ]);
    });

    test('automatically assigns created_by to the authenticated user', function () {
        $taskData = [
            'title' => 'New task',
            'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ];

        $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData)
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'New task',
            'created_by' => $this->user->id,
        ]);
    });

    test('validates required fields', function () {
        $this->actingAs($this->user)
            ->post(route('tasks.store'), [])
            ->assertSessionHasErrors(['title']);
    });

    test('can create a task with a description', function () {
        $taskData = [
            'title' => 'New task with description',
            'description' => 'This is a detailed narrative.',
        ];

        $this->actingAs($this->user)
            ->post(route('tasks.store'), $taskData)
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'title' => 'New task with description',
            'description' => 'This is a detailed narrative.',
        ]);
    });
});

describe('update', function () {
    test('updates a task owned by the team', function () {
        $task = Task::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->user)
            ->put(route('tasks.update', $task), [
                'title' => 'Updated title',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated title',
        ]);
    });

    test('cannot update a task from another team', function () {
        $task = Task::factory()->create(['team_id' => $this->otherTeam->id]);

        $this->actingAs($this->user)
            ->put(route('tasks.update', $task), ['title' => 'Test'])
            ->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
            'title' => 'Test',
        ]);
    });

    test('updates a task description', function () {
        $task = Task::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->user)
            ->put(route('tasks.update', $task), [
                'description' => 'Updated description detail.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'description' => 'Updated description detail.',
        ]);
    });
});

describe('destroy', function () {
    test('deletes a task owned by the users team', function () {
        $task = Task::factory()->create(['team_id' => $this->team->id]);

        $this->actingAs($this->user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    });

    test('cannot delete a task from another team', function () {
        $task = Task::factory()->create(['team_id' => $this->otherTeam->id]);

        $this->actingAs($this->user)
            ->delete(route('tasks.destroy', $task))
            ->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    });
});
