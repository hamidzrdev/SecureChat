<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chat_id' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9._-]+$/'],
            'set_password' => ['nullable', 'boolean'],
            'password' => [
                'nullable',
                'string',
                'min:6',
                'max:64',
                Rule::requiredIf(fn (): bool => $this->boolean('set_password')),
            ],
            'password_confirmation' => [
                'nullable',
                'string',
                'min:6',
                'max:64',
                Rule::requiredIf(fn (): bool => $this->boolean('set_password')),
                'same:password',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chat_id.required' => __('chat.validation.chat_id_required'),
            'chat_id.min' => __('chat.validation.chat_id_min'),
            'chat_id.max' => __('chat.validation.chat_id_max'),
            'chat_id.regex' => __('chat.validation.chat_id_format'),
            'password.min' => __('chat.validation.password_min'),
            'password.max' => __('chat.validation.password_max'),
            'password_confirmation.min' => __('chat.validation.password_min'),
            'password_confirmation.max' => __('chat.validation.password_max'),
            'password_confirmation.same' => __('chat.validation.password_confirmation_same'),
        ];
    }
}
