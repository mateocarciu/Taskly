<?php

namespace App\Services;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

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

    /**
     * Build the notification dropdown payload for the shared Inertia props.
     *
     * @return array{unreadCount: int, data: array<int, array<string, mixed>>, pagination: array{current_page: int, last_page: int, total: int, has_more: bool}}
     */
    public function dropdown(?User $user, Request $request): array
    {
        if (! $user) {
            return [
                'unreadCount' => 0,
                'data' => [],
                'pagination' => ['current_page' => 1, 'last_page' => 1, 'total' => 0, 'has_more' => false],
            ];
        }

        return [
            'unreadCount' => $this->unreadCountFor($user),
            ...NotificationResource::paginated($this->paginateFor($user), $request),
        ];
    }

    /**
     * An empty first page, for guests and users without notifications.
     */
    public static function emptyPaginator(): LengthAwarePaginator
    {
        return new Paginator([], 1, self::PER_PAGE, 1);
    }
}
