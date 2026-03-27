<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Column;
use App\Models\User;
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

        Task::where('column_id', $columnId)->increment('order');
        $order = 0;

        unset($data['column_id']);

        return Task::create(array_merge($data, [
            'team_id' => $user->team_id,
            'created_by' => $user->id,
            'column_id' => $columnId,
            'order' => $order,
            'column_updated_at' => now(), // Initialize column timing
        ]));
    }

    /**
     * Update an existing task.
     */
    public function updateTask(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    /**
     * Update the sequence and column of a task inside the Kanban board.
     */
    public function updateSequence(Task $task, int $newColumnId, int $newOrder): void
    {
        $oldColumnId = $task->column_id;
        $oldOrder = $task->order;
        $isDifferentColumn = $oldColumnId != $newColumnId;

        DB::transaction(function () use ($task, $newColumnId, $newOrder, $oldColumnId, $oldOrder, $isDifferentColumn) {
            if (!$isDifferentColumn) {
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

            $updateData = [
                'column_id' => $newColumnId,
                'order' => $newOrder,
            ];

            // If it moved to a new column, reset its timer and accumulate the history
            if ($isDifferentColumn) {
                $timeInOldColumn = $task->column_updated_at ? (int) abs($task->column_updated_at->diffInSeconds(now())) : 0;
                $history = $task->time_spent_in_columns ?? [];
                $history[$oldColumnId] = ($history[$oldColumnId] ?? 0) + $timeInOldColumn;

                $updateData['time_spent_in_columns'] = $history;
                $updateData['column_updated_at'] = now();
            }

            $task->update($updateData);
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
