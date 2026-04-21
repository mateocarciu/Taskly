<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Column;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ColumnTaskController extends Controller
{
    public function index(Request $request, Column $column): AnonymousResourceCollection
    {
        $this->authorize('view', $column);

        $tasks = $column->tasks()
            ->with('creator:id,name')
            ->orderBy('order')
            ->paginate(10);

        return TaskResource::collection($tasks);
    }
}
