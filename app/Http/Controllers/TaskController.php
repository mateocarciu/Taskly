<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskCreateRequest;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TaskUpdateRequest;
use App\Jobs\IncrementTeamCompletedTasks;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Tasks', [
            'tasks' => Task::query()
                ->with('creator:id,name')
                ->where('team_id', $request->user()->team_id)
                ->orderBy('due_date')
                ->paginate(5),
        ]);
    }

    public function store(TaskCreateRequest $request): RedirectResponse
    {
        Task::query()->create([
            ...$request->validated(),
            'team_id' => $request->user()->team_id,
            'created_by' => $request->user()->id,
        ]);

        return to_route('tasks.index');
    }

    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        if ($task->team_id !== $request->user()->team_id) {
            abort(403, 'You are not authorized to update this task.');
        }

        $task->update($request->validated());

        if ($task->completed) {
            IncrementTeamCompletedTasks::dispatch($task->team_id);
        }

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        if ($task->team_id !== $request->user()->team_id) {
            abort(403, 'You are not authorized to delete this task.');
        }

        $task->delete();

        return back();
    }
}
