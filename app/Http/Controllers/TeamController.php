<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamMemberStoreRequest;
use App\Http\Requests\TeamSaveRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(private TeamService $teamService) {}

    /**
     * Settings / Teams management page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->team_id ? $user->team()->with('memberships.user')->first() : null;

        return Inertia::render('settings/Teams', [
            'teams' => $this->teamService->listForSettings($user)
                ->map(fn (Team $team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'users_count' => $team->users_count,
                    'is_current' => $user->team_id === $team->id,
                ]),
            'currentTeamId' => $user->team_id,
            'members' => $team ? $this->teamService->members($team) : [],
            'availableUsers' => $team ? $this->teamService->availableUsers($team) : [],
            'canManage' => $user->isPrivileged(),
            'isOwner' => $user->isOwner(),
        ]);
    }

    /**
     * Show a "pending" notice for users who have not been added to any team.
     */
    public function pending(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->team_id !== null) {
            return redirect()->route('dashboard');
        }

        if ($user->isPrivileged()) {
            return redirect()->route('teams.index');
        }

        return Inertia::render('teams/Pending');
    }

    /**
     * Create a new team (owner/admin only).
     */
    public function store(TeamSaveRequest $request): RedirectResponse
    {
        $this->authorize('create', Team::class);

        $this->teamService->create($request->validated('name'), $request->user());

        return redirect()->route('teams.index');
    }

    /**
     * Rename a team (owner/admin only).
     */
    public function update(TeamSaveRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('rename', $team);

        $this->teamService->rename($team, $request->validated('name'));

        return redirect()->route('teams.index');
    }

    /**
     * Delete a team (owner only).
     */
    public function destroy(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $this->teamService->delete($team);

        return redirect()->route('teams.index');
    }

    /**
     * Switch the authenticated user's active team context.
     */
    public function switch(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('switch', $team);

        $this->teamService->setActiveTeam($request->user(), $team);

        return back();
    }

    /**
     * Add an existing user (by email) to a team (owner/admin only).
     */
    public function addMember(TeamMemberStoreRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('addMember', $team);

        $user = User::query()->findOrFail($request->validated('user_id'));

        $this->teamService->addMember($team, $user);

        return redirect()->route('teams.index')
            ->with('success', "{$user->name} added to {$team->name}.");
    }

    /**
     * Remove a user from a team (owner can remove anyone; admin can remove members only).
     */
    public function removeMember(Request $request, Team $team, User $user): RedirectResponse
    {
        $this->authorize('removeMember', [$team, $user]);

        $this->teamService->removeMember($team, $user);

        return redirect()->route('teams.index');
    }

    /**
     * Promote a member to admin (owner/admin can promote plain members).
     */
    public function promote(Request $request, Team $team, User $user): RedirectResponse
    {
        $this->authorize('promote', [$team, $user]);

        $this->teamService->promoteToAdmin($user);

        return redirect()->route('teams.index');
    }

    /**
     * Demote an admin back to member (owner only).
     */
    public function demote(Request $request, Team $team, User $user): RedirectResponse
    {
        $this->authorize('demote', [$team, $user]);

        $this->teamService->demoteToMember($user, $team);

        return redirect()->route('teams.index');
    }
}
