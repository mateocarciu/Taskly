<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;
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
        $teamId = $this->user()?->team_id;

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'created_by' => ['sometimes', 'nullable', 'integer', $this->teamMemberRule($teamId)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', $this->teamMemberRule($teamId)],
            'tag_ids' => [
                'sometimes',
                'array',
                Rule::exists('tags', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,png,svg,jpeg,jpg', 'max:20480'],
            'removed_attachment_ids' => ['sometimes', 'array'],
            'removed_attachment_ids.*' => ['uuid'],
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
