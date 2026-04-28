<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'team_name' => $this->resource['team']->name,
            'team_members' => $this->resource['team']->users_count,
            'total_tasks' => $this->resource['total_tasks'],
            'overdue_tasks' => $this->resource['overdue_tasks']->count(),
            'due_today_tasks' => $this->resource['due_today_tasks']->count(),
            'column_stats' => $this->resource['columns']->map(fn($column) => [
                'id' => $column->id,
                'name' => $column->name,
                'count' => $column->tasks_count,
            ])->values(),
            'attention_tasks' => $this->resource['overdue_tasks']
                ->concat($this->resource['due_today_tasks'])
                ->unique('id')
                ->values(),
            'recent_tasks' => $this->resource['recent_tasks'],
        ];
    }
}
