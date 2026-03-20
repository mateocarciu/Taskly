<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaskSequenceController extends Controller
{
    public function update(Request $request, Task $task): RedirectResponse
    {
        if ($task->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $validated = $request->validate([
            'column_id' => ['required', 'exists:columns,id'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $newColumnId = $validated['column_id'];
        $newOrder = $validated['order'];
        
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
                // Shift tasks in old column down
                Task::where('column_id', $oldColumnId)
                    ->where('order', '>', $oldOrder)
                    ->decrement('order');

                // Shift tasks in new column up to make room
                Task::where('column_id', $newColumnId)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');
            }

            $task->update([
                'column_id' => $newColumnId,
                'order' => $newOrder,
            ]);
        });

        return back();
    }
}
