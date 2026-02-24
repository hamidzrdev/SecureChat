<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatStorePassphraseVerifyBlobRequest extends FormRequest
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
            'verify_blob_base64' => ['required', 'string', 'max:65535'],
            'iter' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'verify_blob_base64.required' => __('chat.validation.verify_blob_required'),
            'verify_blob_base64.string' => __('chat.validation.verify_blob_string'),
            'verify_blob_base64.max' => __('chat.validation.verify_blob_max'),
            'iter.integer' => __('chat.validation.iter_integer'),
            'iter.min' => __('chat.validation.iter_min'),
        ];
    }
}
