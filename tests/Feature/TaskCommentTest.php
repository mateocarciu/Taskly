<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
    $this->otherTeam = Team::factory()->create();
    $this->column = Column::create([
        'team_id' => $this->team->id,
        'name' => 'To Do',
        'order' => 1,
    ]);
});

describe('comments', function () {
    test('stores a comment on the task detail page', function () {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $this->column->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'Progress is good, moving to review.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'body' => 'Progress is good, moving to review.',
        ]);
    });

    test('prevents commenting on another team task', function () {
        $task = Task::factory()->create(['team_id' => $this->otherTeam->id]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'Should not be allowed.',
            ])
            ->assertStatus(403);
    });

    test('stores a reply to an existing task comment', function () {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $this->column->id,
        ]);

        $parent = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'body' => 'Initial topic',
        ]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'This is a threaded reply.',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
            'body' => 'This is a threaded reply.',
        ]);
    });

    test('rejects replies that target comment from another task', function () {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $this->column->id,
        ]);

        $otherTask = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $this->column->id,
        ]);

        $foreignParent = TaskComment::create([
            'task_id' => $otherTask->id,
            'user_id' => $this->user->id,
            'body' => 'Other task comment',
        ]);

        $this->actingAs($this->user)
            ->post(route('tasks.comments.store', $task), [
                'body' => 'Invalid reply target',
                'parent_id' => $foreignParent->id,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    test('includes threaded replies in tasks page payload', function () {
        $task = Task::factory()->create([
            'team_id' => $this->team->id,
            'column_id' => $this->column->id,
        ]);

        $parent = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'body' => 'Top level comment',
        ]);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
            'body' => 'Nested reply',
        ]);

        $this->actingAs($this->user)
            ->get(route('tasks.index'))
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Tasks')
                    ->where('columns.0.tasks.0.comments.0.body', 'Top level comment')
                    ->where('columns.0.tasks.0.comments.0.replies.0.body', 'Nested reply')
            );
    });
});
