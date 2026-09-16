<?php

namespace App\Http\Resources;

use App\Notifications\EventInvitation;
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
