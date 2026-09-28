<?php

namespace App\Http\Resources;

use App\Notifications\EventInvitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Map backend notification classes to frontend-friendly aliases.
     *
     * When adding a new notification type (e.g. TaskAssigned), register it
     * here to use it in the frontend.
     *
     * @var array<class-string, string>
     */
    private const TYPE_ALIASES = [
        EventInvitation::class => 'event_invitation',
    ];

    /**
     * Transform a page of notifications into the payload the dropdown loads.
     *
     * @return array{data: array<int, array<string, mixed>>, pagination: array{current_page: int, last_page: int, total: int, has_more: bool}}
     */
    public static function paginated(LengthAwarePaginator $notifications, Request $request): array
    {
        return [
            'data' => self::collection($notifications->items())->resolve($request),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'has_more' => $notifications->hasMorePages(),
            ],
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => self::TYPE_ALIASES[$this->type] ?? $this->type,
            'data' => $this->data,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
