<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventIndexResource extends JsonResource
{
    /**
     * Lightweight representation for the calendar grid.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDeclined = false;
        if ($this->relationLoaded('attendees')) {
            $currentUserAttendee = $this->attendees->firstWhere('id', $request->user()?->id);
            $isDeclined = $currentUserAttendee?->pivot?->participation_status === 'declined';
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'has_video' => $this->has_video,
            'is_declined' => $isDeclined,
        ];
    }
}
