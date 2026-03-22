<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Column;
use App\Models\User;
use App\Jobs\IncrementTeamCompletedTasks;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Create a new task.
     */
    public function createTask(array $data, User $user): Task
    {
        $columnId = $data['column_id'] ?? null;

        if (!$columnId) {
            $columnId = Column::where('team_id', $user->team_id)->orderBy('order')->value('id');
        }

        $order = Task::where('column_id', $columnId)->max('order');
        $order = $order !== null ? $order + 1 : 0;

        unset($data['column_id']);

        return Task::create(array_merge($data, [
            'team_id' => $user->team_id,
            'created_by' => $user->id,
            'column_id' => $columnId,
            'order' => $order,
        ]));
    }

    /**
     * Update an existing task.
     */
    public function updateTask(Task $task, array $data): bool
    {
        $updated = $task->update($data);

        if ($updated && isset($data['completed']) && $data['completed']) {
            IncrementTeamCompletedTasks::dispatch($task->team_id);
        }

        return $updated;
    }

    /**
     * Update the sequence and column of a task inside the Kanban board.
     */
    public function updateSequence(Task $task, int $newColumnId, int $newOrder): void
    {
        $oldColumnId = $task->column_id;
        $oldOrder = $task->order;

        DB::transaction(function () use ($task, $newColumnId, $newOrder, $oldColumnId, $oldOrder) {
            if ($oldColumnId == $newColumnId) {
                // Moving within the same column
                if ($oldOrder < $newOrder) {
                    Task::where('column_id', $newColumnId)
                        ->whereBetween('order', [$oldOrder + 1, $newOrder])
                        ->decrement('order');
                } elseif ($oldOrder > $newOrder) {
                    Task::where('column_id', $newColumnId)
                        ->whereBetween('order', [$newOrder, $oldOrder - 1])
                        ->increment('order');
                }
            } else {
                // Moving to a different column
                Task::where('column_id', $oldColumnId)
                    ->where('order', '>', $oldOrder)
                    ->decrement('order');

                Task::where('column_id', $newColumnId)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            }

            $task->update([
                'column_id' => $newColumnId,
                'order' => $newOrder,
            ]);
        });
    }

    /**
     * Delete a task.
     */
    public function deleteTask(Task $task): ?bool
    {
        return $task->delete();
    }
}
