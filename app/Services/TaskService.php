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

        $columnName = Column::query()->where('id', $columnId)->value('name');

        return DB::transaction(function () use ($data, $user, $columnId, $order, $columnName) {
            $task = Task::create(array_merge($data, [
                'team_id' => $user->team_id,
                'created_by' => $user->id,
                'column_id' => $columnId,
                'order' => $order,
                'column_updated_at' => now(), // Initialize column timing
            ]));

            $task->events()->create([
                'team_id' => $task->team_id,
                'actor_id' => $user->id,
                'type' => 'created',
                'metadata' => [
                    'column_id' => $columnId,
                    'column_name' => $columnName,
                ],
            ]);

            if (!empty($task->assigned_to)) {
                $assignedUserName = User::query()->where('id', $task->assigned_to)->value('name');

                $task->events()->create([
                    'team_id' => $task->team_id,
                    'actor_id' => $user->id,
                    'type' => 'assigned',
                    'metadata' => [
                        'assigned_to' => $task->assigned_to,
                        'assigned_to_name' => $assignedUserName,
                    ],
                ]);
            }

            return $task;
        });
    }

    /**
     * Update an existing task.
     */
    public function updateTask(Task $task, array $data, User $actor): bool
    {
        $oldAssignedTo = $task->assigned_to;
        $updated = $task->update($data);

        if (!$updated) {
            return false;
        }

        if (array_key_exists('assigned_to', $data) && $oldAssignedTo !== $task->assigned_to) {
            $assignedUserName = $task->assigned_to
                ? User::query()->where('id', $task->assigned_to)->value('name')
                : null;

            $task->events()->create([
                'team_id' => $task->team_id,
                'actor_id' => $actor->id,
                'type' => 'assigned',
                'metadata' => [
                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $assignedUserName,
                    'previous_assigned_to' => $oldAssignedTo,
                ],
            ]);
        }

        return true;
    }

    /**
     * Update the sequence and column of a task inside the Kanban board.
     */
    public function updateSequence(Task $task, int $newColumnId, int $newOrder, User $actor): void
    {
        $oldColumnId = $task->column_id;
        $oldOrder = $task->order;
        $isDifferentColumn = $oldColumnId != $newColumnId;
        $columnNames = Column::query()
            ->whereIn('id', [$oldColumnId, $newColumnId])
            ->pluck('name', 'id');

        DB::transaction(function () use ($task, $newColumnId, $newOrder, $oldColumnId, $oldOrder, $isDifferentColumn, $actor, $columnNames) {
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

            if ($isDifferentColumn) {
                $task->events()->create([
                    'team_id' => $task->team_id,
                    'actor_id' => $actor->id,
                    'type' => 'moved',
                    'metadata' => [
                        'from_column_id' => $oldColumnId,
                        'from_column_name' => $columnNames[$oldColumnId] ?? null,
                        'to_column_id' => $newColumnId,
                        'to_column_name' => $columnNames[$newColumnId] ?? null,
                    ],
                ]);
            }
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
