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
        $this->authorize('updateSequence', $task);

        $validated = $request->validated();

        $this->taskService->updateSequence($task, $validated['column_id'], $validated['order'], $request->user());

        return back();
    }
}
