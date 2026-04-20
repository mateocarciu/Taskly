<?php

namespace App\Http\Controllers;

use App\Http\Requests\ColumnSequenceUpdateRequest;
use App\Http\Requests\ColumnStoreRequest;
use App\Http\Requests\ColumnUpdateRequest;
use App\Models\Column;
use App\Services\ColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ColumnController extends Controller
{
    public function __construct(private ColumnService $columnService)
    {
    }

    public function store(ColumnStoreRequest $request): RedirectResponse
    {
        $this->columnService->createColumn($request->validated(), $request->user());

        return back();
    }

    public function update(ColumnUpdateRequest $request, Column $column): RedirectResponse
    {
        if ($column->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $this->columnService->updateColumn($column, $request->validated());

        return back();
    }

    public function destroy(Request $request, Column $column): RedirectResponse
    {
        if ($column->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $this->columnService->deleteColumn($column);

        return back();
    }

    public function updateSequence(ColumnSequenceUpdateRequest $request, Column $column): RedirectResponse
    {
        if ($column->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $this->columnService->updateSequence($column, $request->validated()['order']);

        return back();
    }
}
