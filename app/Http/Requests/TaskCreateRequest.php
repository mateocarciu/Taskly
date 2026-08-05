<?php

namespace App\Http\Requests;

use App\Models\Column;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TaskCreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = $this->user()?->team_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
            'created_by' => ['nullable', 'integer', $this->teamMemberRule($teamId)],
            'assigned_to' => ['nullable', 'integer', $this->teamMemberRule($teamId)],
            'tag_ids' => [
                'sometimes',
                'array',
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'column_id' => [
                'nullable',
                Rule::exists('columns', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,png,svg,jpeg,jpg', 'max:20480'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     * @return array<int, \Closure(\Illuminate\Validation\Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $teamId = $this->user()?->team_id;

                if (! $teamId) {
                    return;
                }

                if (! Column::query()->where('team_id', $teamId)->exists()) {
                    $validator->errors()->add('column_id', 'You need at least one column before creating a task.');
                }
            },
        ];
    }

    private function teamMemberRule(?int $teamId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($teamId): void {
            if ($value !== null && ! User::inTeam($teamId)->whereKey($value)->exists()) {
                $fail('The selected user is not part of this team.');
            }
        };
    }
}
