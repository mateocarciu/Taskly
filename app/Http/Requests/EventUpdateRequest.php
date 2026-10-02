<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\EventRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventUpdateRequest extends FormRequest
{
    use EventRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->route('event');
        $teamId = $event?->calendar?->team_id ?? $this->user()?->team_id;

        return $this->eventRules($teamId);
    }
}
