<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskCommentStoreRequest;
use App\Http\Requests\TaskCommentUpdateRequest;
use App\Http\Requests\TaskCreateRequest;
use App\Http\Requests\TaskListRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Resources\ColumnResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\CommentService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
        private CommentService $commentService
    ) {}

    public function index(TaskListRequest $request): Response
    {
        $filters = $request->validated();

        $data = $this->taskService->indexData($request->user(), $filters);

        return Inertia::render('Tasks', [
            'columns' => Inertia::defer(fn () => ColumnResource::collection($data['columns'])),
            'teamMembers' => $data['teamMembers'],
            'tags' => TagResource::collection($data['tags'])->resolve($request),
            'filters' => $filters,
        ]);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(
            (new TaskResource($this->taskService->loadDetails($task)))->resolve()
        );
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

    public function indexComments(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json([
            'comments' => CommentResource::collection(
                $this->commentService->listComments($task)
            )->resolve(),
        ]);
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
