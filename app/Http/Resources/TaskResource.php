<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
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
        ];
    }
}
