<?php

namespace App\Services;

use App\Models\Column;
use App\Models\User;
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
