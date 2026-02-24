<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatSendTextMessageRequest extends FormRequest
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
        return [
            'text' => ['nullable', 'string', 'max:'.((int) config('chat.max_text_length', 2000))],
            'ciphertext_base64' => ['nullable', 'string', 'max:65535'],
            'crypto_meta' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.max' => __('chat.validation.text_max'),
            'ciphertext_base64.max' => __('chat.validation.ciphertext_max'),
            'crypto_meta.array' => __('chat.validation.crypto_meta_array'),
        ];
    }
}
