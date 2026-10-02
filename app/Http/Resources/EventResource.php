<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'calendar_id' => $this->calendar_id,
            'organizer_id' => $this->organizer_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'timezone' => $this->timezone,
            'room_name' => $this->room_name,
            'has_video' => $this->has_video,
            'status' => $this->status,
            'organizer' => $this->whenLoaded('organizer', fn () => [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
            ]),
            'attendees' => $this->whenLoaded('attendees', fn () => $this->attendees
                ->map(fn ($attendee) => [
                    'id' => $attendee->id,
                    'name' => $attendee->name,
                ])->values()),
        ];
    }
}
