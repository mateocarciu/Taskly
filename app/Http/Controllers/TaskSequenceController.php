<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TaskSequenceController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function update(Request $request, Task $task): RedirectResponse
    {
        if ($task->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $validated = $request->validate([
            'column_id' => ['required', 'exists:columns,id'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $this->taskService->updateSequence($task, $validated['column_id'], $validated['order']);

        return back();
    }
}
