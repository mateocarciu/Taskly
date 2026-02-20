<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamJoinRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function select(Request $request): Response|RedirectResponse
    {
        if ($request->user()->team_id) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('teams/SelectTeam', [
            'teams' => Team::all(['id', 'name']),
        ]);
    }

    public function join(TeamJoinRequest $request): RedirectResponse
    {
        $request->user()->update([
            'team_id' => $request->validated('team_id'),
        ]);

        return redirect()->route('dashboard');
    }
}
