<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskSequenceUpdateRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;

class TaskSequenceController extends Controller
{
    public function __construct(private TaskService $taskService)
    {
    }

    public function update(TaskSequenceUpdateRequest $request, Task $task): RedirectResponse
    {
        if ($task->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $validated = $request->validated();

        $this->taskService->updateSequence($task, $validated['column_id'], $validated['order'], $request->user());

        return back();
    }
}
