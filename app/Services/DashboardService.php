<?php

namespace App\Services;

use App\Models\Column;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array{team: Team, team_member_count: int, columns: Collection<int, Column>, total_tasks: int, overdue_tasks: Collection<int, Task>, due_today_tasks: Collection<int, Task>, recent_tasks: Collection<int, Task>}
     */
    public function stats(User $user): array
    {
        $team = Team::query()->findOrFail($user->team_id);

        $memberIds = $team->memberships()->pluck('user_id');
        $privilegedCount = User::query()
            ->whereIn('role', ['owner', 'admin'])
            ->whereNotIn('id', $memberIds)
            ->count();

        $columns = Column::query()
            ->where('team_id', $team->id)
            ->orderBy('order')
            ->withCount('tasks')
            ->get();

        $baseTasksQuery = Task::query()->where('team_id', $team->id);

        $totalTasks = (clone $baseTasksQuery)->count();

        $overdueTasks = (clone $baseTasksQuery)
            ->whereHas('column', fn ($query) => $query->where('type', '!=', 'done'))
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->with(['column:id,name'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (Task $task) => $this->attentionTask($task, 'Overdue'));

        $dueTodayTasks = (clone $baseTasksQuery)
            ->whereHas('column', fn ($query) => $query->where('type', '!=', 'done'))
            ->whereNotNull('due_date')
            ->whereDate('due_date', today())
            ->with(['column:id,name'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (Task $task) => $this->attentionTask($task, 'Due today'));

        $recentTasks = (clone $baseTasksQuery)
            ->with(['column:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'column_name' => $task->column?->name ?? 'Unassigned',
                'assignee_name' => $task->assignee?->name,
                'due_date' => $task->due_date?->toIso8601String(),
                'comments_count' => $task->comments_count,
            ]);

        return [
            'team' => $team,
            'team_member_count' => $memberIds->count() + $privilegedCount,
            'columns' => $columns,
            'total_tasks' => $totalTasks,
            'overdue_tasks' => $overdueTasks,
            'due_today_tasks' => $dueTodayTasks,
            'recent_tasks' => $recentTasks,
        ];
    }

    private function attentionTask(Task $task, string $reason): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'column_name' => $task->column?->name ?? 'Unassigned',
            'due_date' => $task->due_date?->toIso8601String(),
            'reason' => $reason,
        ];
    }
}
