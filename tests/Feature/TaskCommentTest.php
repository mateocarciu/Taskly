<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

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
});
