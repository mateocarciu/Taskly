<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnershipTransferRequest;
use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('settings/Users', [
            'users' => $this->userService->list(),
            'isOwner' => $request->user()->isOwner(),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->userService->create($request->validated(), $request->user());

        return redirect()->route('settings.users.index')
            ->with('success', 'User created successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return redirect()->route('settings.users.index');
    }

    public function transferOwnership(OwnershipTransferRequest $request): RedirectResponse
    {
        $this->authorize('transferOwnership', User::class);

        $target = $this->userService->transferOwnership($request->user(), $request->validated('user_id'));

        return redirect()->route('settings.users.index')
            ->with('success', "Ownership transferred to {$target->name}.");
    }
}
