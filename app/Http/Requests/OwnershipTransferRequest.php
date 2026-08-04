<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OwnershipTransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ((int) $value === $this->user()->id) {
                        $fail('You cannot transfer ownership to yourself.');
                    }
                },
            ],
        ];
    }
}
