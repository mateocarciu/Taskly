<?php

namespace App\Http\Controllers;

use App\Models\Column;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ColumnController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $order = Column::where('team_id', $request->user()->team_id)->max('order') + 1 ?? 0;

        Column::create([
            'name' => $validated['name'],
            'team_id' => $request->user()->team_id,
            'order' => $order,
        ]);

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

        $column->update($validated);

        return back();
    }

    public function destroy(Request $request, Column $column): RedirectResponse
    {
        if ($column->team_id !== $request->user()->team_id) {
            abort(403);
        }

        $column->delete();

        return back();
    }
}
