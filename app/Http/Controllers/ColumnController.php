<?php

namespace App\Http\Controllers;

use App\Models\Column;
use App\Services\ColumnService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ColumnController extends Controller
{
    public function __construct(private ColumnService $columnService) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->columnService->createColumn($validated, $request->user());

        return back();
    }

    public function update(Request $request, Column $column): RedirectResponse
    {
        if ($column->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->columnService->updateColumn($column, $validated);

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
}
