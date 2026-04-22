<?php

namespace App\Http\Requests;

use App\Models\Column;
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn($query) => $query->where('team_id', $this->user()?->team_id)
                ),
            ],
            'column_id' => [
                'nullable',
                Rule::exists('columns', 'id')->where(fn($query) => $query->where('team_id', $this->user()?->team_id)),
            ],
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

                if (!$teamId) {
                    return;
                }

                $hasColumn = Column::query()
                    ->where('team_id', $teamId)
                    ->exists();

                if (!$hasColumn) {
                    $validator->errors()->add('column_id', 'You need at least one column before creating a task.');
                }
            },
        ];
    }
}
