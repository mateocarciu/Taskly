<?php

use App\Jobs\IncrementTeamCompletedTasks;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->team = Team::factory()->create(['count_completed_tasks' => 0]);
    $this->user = User::factory()->create(['team_id' => $this->team->id]);
});

test('job increments team completed tasks counter', function () {
    $job = new IncrementTeamCompletedTasks($this->team->id);
    $job->handle();

    expect($this->team->fresh()->count_completed_tasks)->toBe(1);
});

test('job is dispatched when task is marked as completed', function () {
    Queue::fake();

    $task = Task::factory()->create([
        'team_id' => $this->team->id,
        'completed' => false,
    ]);

    $this->actingAs($this->user)
        ->put(route('tasks.update', $task), ['completed' => true]);

    Queue::assertPushed(IncrementTeamCompletedTasks::class, function ($job) {
        return $job->teamId === $this->team->id;
    });
});
