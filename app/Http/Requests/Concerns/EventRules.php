<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait EventRules
{
    /**
     * Common validation rules shared by event store and update requests.
     *
     * @return array<string, array<mixed>>
     */
    protected function eventRules(int $teamId): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'timezone' => ['sometimes', 'string', 'max:50'],
            'has_video' => ['sometimes', 'boolean'],
            'attendee_ids' => ['sometimes', 'array'],
            'attendee_ids.*' => ['integer', Rule::exists('users', 'id')->where(
                fn ($query) => $query->where('team_id', $teamId)
            )],
        ];
    }
}
