<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assigned_to' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')->where(
                    fn($query) => $query->where('team_id', $this->user()?->team_id)
                ),
            ],
            'tag_ids' => [
                'sometimes',
                'array',
                Rule::exists('tags', 'id')->where(
                    fn($query) => $query->where('team_id', $this->user()?->team_id)
                ),
            ],
        ];
    }
}
