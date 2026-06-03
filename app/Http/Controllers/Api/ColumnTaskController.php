<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Http\Resources\TaskResource;
use App\Http\Requests\TaskListRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ColumnTaskController extends Controller
{
    public function index(TaskListRequest $request, Column $column): AnonymousResourceCollection
    {
        $this->authorize('view', $column);

        $filters = $request->validated();

        $tasks = $column->tasks()
            ->with([
                'creator:id,name',
                'assignee:id,name',
                'tags:id,name,color',
            ])
            ->filter($filters)
            ->orderBy('order')
            ->paginate(10);

        return TaskResource::collection($tasks);
    }
}
