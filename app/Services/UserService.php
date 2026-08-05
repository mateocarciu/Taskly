<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @return array<int, array{id: int, name: string, email: string, role: string}>
     */
    public function list(): array
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ])
            ->all();
    }

    public function create(array $data, User $actor): User
    {
        // Admins cannot create other admins; only the owner can.
        if ($data['role'] === 'admin' && ! $actor->isOwner()) {
            $data['role'] = 'member';
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function transferOwnership(User $currentOwner, int $targetId): User
    {
        $target = User::query()->findOrFail($targetId);

        $currentOwner->update(['role' => 'admin']);
        $target->update(['role' => 'owner']);

        return $target;
    }
}
