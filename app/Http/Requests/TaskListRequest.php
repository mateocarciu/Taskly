<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'assignee_id' => ['nullable', 'string', 'max:50'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer'],
            'due_date' => ['nullable', 'string', 'max:50'],
        ];
    }
}
