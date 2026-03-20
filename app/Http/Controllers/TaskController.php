<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Column;
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
            'columns' => Column::query()
                ->with([
                    'tasks' => function ($query) {
                        $query->with('creator:id,name')->orderBy('order');
                    }
                ])
                ->where('team_id', $request->user()->team_id)
                ->orderBy('order')
                ->get(),
        ]);
    }

    public function store(TaskCreateRequest $request): RedirectResponse
    {
        $columnId = $request->validated('column_id') ?? null;

        if (!$columnId) {
            $columnId = Column::where('team_id', $request->user()->team_id)->orderBy('order')->value('id');
        }

        $order = Task::where('column_id', $columnId)->max('order');
        $order = $order !== null ? $order + 1 : 0;

        Task::query()->create([
            ...$request->safe()->except('column_id'),
            'team_id' => $request->user()->team_id,
            'created_by' => $request->user()->id,
            'column_id' => $columnId,
            'order' => $order,
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
