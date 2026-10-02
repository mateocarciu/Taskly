<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * How many notifications the dropdown shows before the user loads more.
     */
    public const PER_PAGE = 5;

    /**
     * Paginate the notifications of a user, most recent first.
     */
    public function paginateFor(User $user): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate(self::PER_PAGE);
    }

    /**
     * Count the notifications a user has not read yet.
     */
    public function unreadCountFor(User $user): int
    {
        return $user->unreadNotifications()->count();
    }
}
