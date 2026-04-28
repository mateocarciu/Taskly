<?php

use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create(['team_id' => $team->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard shows team task statistics', function () {
    Carbon::setTestNow('2026-04-27 10:00:00');

    $team = Team::factory()->create(['name' => 'Acme Team']);
    $user = User::factory()->create(['team_id' => $team->id]);

    $todo = Column::create(['team_id' => $team->id, 'name' => 'To Do', 'order' => 1]);
    $progress = Column::create(['team_id' => $team->id, 'name' => 'In Progress', 'order' => 2]);
    $done = Column::create(['team_id' => $team->id, 'name' => 'Done', 'order' => 3]);

    Task::factory()->create([
        'team_id' => $team->id,
        'column_id' => $todo->id,
        'due_date' => now()->addDay(),
        'column_updated_at' => now()->subHour(),
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'column_id' => $progress->id,
        'due_date' => now()->subDay(),
        'column_updated_at' => now()->subDays(6),
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'column_id' => $progress->id,
        'due_date' => now()->addWeek(),
        'column_updated_at' => now()->subDays(6),
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'column_id' => $done->id,
        'due_date' => now()->subDay(),
        'column_updated_at' => now()->subDays(2),
    ]);

    Task::factory()->create([
        'team_id' => $team->id,
        'column_id' => $done->id,
        'due_date' => now()->addDay(),
        'column_updated_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($team) {
            return $page
                ->component('Dashboard')
                ->where('stats.team_name', $team->name)
                ->where('stats.team_members', 1)
                ->where('stats.total_tasks', 5)
                ->where('stats.overdue_tasks', 2)
                ->where('stats.due_today_tasks', 0)
                ->has('stats.column_stats', 3)
                ->where('stats.column_stats.0.count', 1)
                ->where('stats.column_stats.1.count', 2)
                ->where('stats.column_stats.2.count', 2)
                ->has('stats.attention_tasks', 2)
                ->has('stats.recent_tasks', 5);
        });

    Carbon::setTestNow();
});