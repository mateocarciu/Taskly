<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ColumnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tasksData = [];
        $pagination = null;

        if ($this->relationLoaded('tasks')) {
            $tasksData = TaskResource::collection($this->tasks);

            if ($this->tasks instanceof LengthAwarePaginator) {
                $pagination = [
                    'current_page' => $this->tasks->currentPage(),
                    'last_page' => $this->tasks->lastPage(),
                    'total' => $this->tasks->total(),
                    'has_more' => $this->tasks->hasMorePages(),
                ];
            }
        }

        return [
            'id' => $this->id,
            'team_id' => $this->team_id,
            'name' => $this->name,
            'order' => $this->order,
            'tasks' => $tasksData,
            'pagination' => $pagination,
        ];
    }
}
