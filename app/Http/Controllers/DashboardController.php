<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardStatsResource;
use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $team = Team::query()
            ->withCount('users')
            ->findOrFail($request->user()->team_id);

        $columns = Column::query()
            ->where('team_id', $team->id)
            ->orderBy('order')
            ->withCount('tasks')
            ->get();

        $baseTasksQuery = Task::query()->where('team_id', $team->id);

        $totalTasks = (clone $baseTasksQuery)->count();

        $overdueQuery = (clone $baseTasksQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today());

        $dueTodayQuery = (clone $baseTasksQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', today());

        $overdueTasks = $overdueQuery
            ->with(['column:id,name'])
            ->orderBy('due_date')
            ->get()
            ->map(fn(Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'column_name' => $task->column?->name ?? 'Unassigned',
                'due_date' => $task->due_date?->toIso8601String(),
                'reason' => 'Overdue',
            ]);

        $dueTodayTasks = $dueTodayQuery
            ->with(['column:id,name'])
            ->orderBy('due_date')
            ->get()
            ->map(fn(Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'column_name' => $task->column?->name ?? 'Unassigned',
                'due_date' => $task->due_date?->toIso8601String(),
                'reason' => 'Due today',
            ]);

        $recentTasks = (clone $baseTasksQuery)
            ->with(['column:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn(Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'column_name' => $task->column?->name ?? 'Unassigned',
                'assignee_name' => $task->assignee?->name,
                'due_date' => $task->due_date?->toIso8601String(),
                'comments_count' => $task->comments_count,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => DashboardStatsResource::make([
                'team' => $team,
                'columns' => $columns,
                'total_tasks' => $totalTasks,
                'overdue_tasks' => $overdueTasks,
                'due_today_tasks' => $dueTodayTasks,
                'recent_tasks' => $recentTasks,
            ])->resolve($request),
        ]);
    }
}
