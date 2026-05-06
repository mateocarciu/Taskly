<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Column;
use App\Models\Tag;
use App\Models\User;
use App\Services\TaskService;
use App\Http\Resources\ColumnResource;
use App\Http\Resources\TaskResource;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskCreateRequest;
use App\Http\Requests\TaskCommentStoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TaskUpdateRequest;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService)
    {
    }

    public function index(Request $request): Response
    {
        $columns = Column::query()
            ->where('team_id', $request->user()->team_id)
            ->orderBy('order')
            ->get();

        $columns->each(function ($column) {
            $column->setRelation(
                'tasks',
                $column->tasks()
                    ->with([
                        'creator:id,name',
                        'assignee:id,name',
                        'tags:id,name,color',
                    ])
                    ->orderBy('order')
                    ->paginate(10)
            );
        });

        $teamMembers = User::query()
            ->where('team_id', $request->user()->team_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::query()
            ->where('team_id', $request->user()->team_id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Tasks', [
            'columns' => ColumnResource::collection($columns),
            'teamMembers' => $teamMembers,
            'tags' => $tags,
        ]);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load([
            'creator:id,name',
            'assignee:id,name',
            'tags:id,name,color',
            'comments' => fn($query) => $query
                ->whereNull('parent_id')
                ->with(['user:id,name', 'replies']),
            'events.actor:id,name',
        ]);

        return response()->json((new TaskResource($task))->resolve());
    }

    public function store(TaskCreateRequest $request): RedirectResponse
    {
        $this->taskService->createTask($request->safe()->all(), $request->user());

        return to_route('tasks.index');
    }

    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->taskService->updateTask($task, $request->validated(), $request->user());

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->deleteTask($task);

        return back();
    }

    public function storeComment(TaskCommentStoreRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $validated = $request->validated();

        $task->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        return back();
    }
}
