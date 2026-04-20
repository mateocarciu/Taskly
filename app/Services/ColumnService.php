<?php

namespace App\Services;

use App\Models\Column;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ColumnService
{
    /**
     * Create a new column.
     */
    public function createColumn(array $data, User $user): Column
    {
        $order = Column::where('team_id', $user->team_id)->max('order');
        $order = $order !== null ? $order + 1 : 0;

        return Column::create([
            'name' => $data['name'],
            'team_id' => $user->team_id,
            'order' => $order,
        ]);
    }

    /**
     * Update an existing column.
     */
    public function updateColumn(Column $column, array $data): bool
    {
        return $column->update($data);
    }

    /**
     * Update the sequence order of a column.
     */
    public function updateSequence(Column $column, int $newOrder): void
    {
        DB::transaction(function () use ($column, $newOrder): void {
            $columns = Column::query()
                ->where('team_id', $column->team_id)
                ->orderBy('order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $orderedIds = $columns->pluck('id')->values()->all();

            $currentIndex = array_search($column->id, $orderedIds, true);
            if ($currentIndex === false) {
                return;
            }

            $targetIndex = max(0, min($newOrder, count($orderedIds) - 1));
            if ($targetIndex === $currentIndex) {
                return;
            }

            array_splice($orderedIds, $currentIndex, 1);
            array_splice($orderedIds, $targetIndex, 0, [$column->id]);

            foreach ($orderedIds as $index => $columnId) {
                Column::query()
                    ->whereKey($columnId)
                    ->update(['order' => $index]);
            }
        });
    }

    /**
     * Delete a column.
     */
    public function deleteColumn(Column $column): ?bool
    {
        if ($column->tasks()->exists()) {
            throw ValidationException::withMessages([
                'column' => 'Cannot delete a column that contains tasks. Please move or delete the tasks first.',
            ]);
        }

        return $column->delete();
    }
}
