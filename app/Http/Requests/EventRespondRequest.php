<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventRespondRequest extends FormRequest
{
    /**
     * Pull the response out of the route so it can be validated.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'response' => $this->route('response'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'response' => ['required', 'in:accepted,declined'],
        ];
    }
}
