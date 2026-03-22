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
        // Calculate days cleanly or fallback to 0 if null
        $daysInColumn = $this->column_updated_at ? (int) $this->column_updated_at->diffInDays(now()) : 0;

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
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
        ];
    }
}
