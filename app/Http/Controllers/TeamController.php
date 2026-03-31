<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamJoinRequest;
use App\Http\Requests\TeamSaveRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Teams', [
            'teams' => Team::query()
                ->withCount('users')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn(Team $team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'users_count' => $team->users_count,
                    'is_current' => $user->team_id === $team->id,
                ]),
            'currentTeamId' => $user->team_id,
        ]);
    }

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

    public function store(TeamSaveRequest $request): RedirectResponse
    {
        Team::create([
            'name' => $request->validated('name')
        ]);

        return redirect()->route('teams.index');
    }

    public function update(TeamSaveRequest $request, Team $team): RedirectResponse
    {
        if ($request->user()->team_id !== $team->id) {
            abort(403);
        }

        $team->update([
            'name' => $request->validated('name'),
        ]);

        return redirect()->route('teams.index');
    }

    public function switch(Request $request, Team $team): RedirectResponse
    {
        $request->user()->update([
            'team_id' => $team->id,
        ]);

        return back();
    }
}
