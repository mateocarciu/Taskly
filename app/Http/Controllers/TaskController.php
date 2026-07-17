<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Column;
use App\Models\Tag;
use App\Models\User;
use App\Models\TaskComment;
use App\Services\TaskService;
use App\Services\CommentService;
use App\Http\Resources\ColumnResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TaskResource;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskCreateRequest;
use App\Http\Requests\TaskCommentStoreRequest;
use App\Http\Requests\TaskCommentUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Requests\TaskListRequest;
use \Illuminate\Http\Response as HttpResponse;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
        private CommentService $commentService
    ) {
    }

    public function index(TaskListRequest $request): Response
    {
        $filters = $request->validated();

        $columns = Column::query()
            ->where('team_id', $request->user()->team_id)
            ->orderBy('order')
            ->get();

        $columns->each(function ($column) use ($filters) {
            $column->setRelation(
                'tasks',
                $column->tasks()
                    ->with([
                        'creator:id,name',
                        'assignee:id,name',
                        'tags:id,name,color',
                    ])
                    ->filter($filters)
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
            'columns' => Inertia::defer(fn () => ColumnResource::collection($columns)),
            'teamMembers' => $teamMembers,
            'tags' => $tags,
            'filters' => $filters,
        ]);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load([
            'column:id,name,type',
            'creator:id,name',
            'assignee:id,name',
            'tags:id,name,color',
            'taskAttachments',
            'events.actor:id,name',
        ]);

        return response()->json((new TaskResource($task))->resolve());
    }

    public function indexComments(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $comments = $task->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->get();

        return response()->json([
            'comments' => CommentResource::collection($comments)->resolve(),
        ]);
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

    public function storeComment(TaskCommentStoreRequest $request, Task $task): HttpResponse|JsonResponse
    {
        $this->authorize('comment', $task);

        $validated = $request->validated();

        $this->commentService->createComment($task, $validated, $request->user());

        return response()->noContent();
    }

    public function updateComment(TaskCommentUpdateRequest $request, Task $task, TaskComment $comment): HttpResponse|JsonResponse
    {
        $this->authorize('update', $comment);

        $validated = $request->validated();

        $this->commentService->updateComment($comment, $validated);

        return response()->noContent();
    }

    public function destroyComment(Request $request, Task $task, TaskComment $comment): HttpResponse|JsonResponse
    {
        $this->authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return response()->noContent();
    }
}
