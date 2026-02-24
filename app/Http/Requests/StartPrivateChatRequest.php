<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPrivateChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentUserId = $this->user()?->getAuthIdentifier();

        return [
            'target_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn(array_filter([(int) $currentUserId])),
            ],
            'is_passphrase' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_user_id.required' => __('chat.validation.target_user_required'),
            'target_user_id.integer' => __('chat.validation.target_user_integer'),
            'target_user_id.exists' => __('chat.validation.target_user_exists'),
            'target_user_id.not_in' => __('chat.validation.target_user_not_self'),
        ];
    }
}
