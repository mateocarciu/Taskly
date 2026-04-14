<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Serialize a comment and its nested replies.
     *
     * @return array<string, mixed>
     */
    private function serializeComment($comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'parent_id' => $comment->parent_id,
            'created_at' => $comment->created_at?->toIso8601String(),
            'user' => [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ],
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies->map(fn($reply) => $this->serializeComment($reply))->values()
                : [],
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate historical + active seconds for current column properly
        $currentSeconds = $this->column_updated_at ? (int) abs($this->column_updated_at->diffInSeconds(now())) : 0;
        $history = $this->time_spent_in_columns ?? [];
        $historicalSeconds = $history[$this->column_id] ?? 0;

        $totalSeconds = $historicalSeconds + $currentSeconds;
        $daysInColumn = (int) floor($totalSeconds / 86400);

        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'column_id' => $this->column_id,
            'order' => $this->order,
            'title' => $this->title,
            'description' => $this->description,
            'days_in_column' => $daysInColumn,
            'due_date' => $this->due_date,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'creator' => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn() => [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ]),
            'comments' => $this->whenLoaded(
                'comments',
                fn() => $this->comments
                    ->map(fn($comment) => $this->serializeComment($comment))
                    ->values()
            ),
            'events' => $this->whenLoaded(
                'events',
                fn() => $this->events->map(fn($event) => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'actor' => $event->relationLoaded('actor') && $event->actor
                        ? [
                            'id' => $event->actor->id,
                            'name' => $event->actor->name,
                        ]
                        : null,
                    'metadata' => $event->metadata ?? [],
                ])->values(),
            ),
        ];
    }
}
