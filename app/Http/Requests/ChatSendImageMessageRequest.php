<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatSendImageMessageRequest extends FormRequest
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
        $allowedMimeTypes = implode(',', (array) config('chat.allowed_mime_types', []));
        $allowedExtensions = implode(',', (array) config('chat.allowed_extensions', []));

        return [
            'image' => [
                'required',
                'file',
                'max:'.((int) config('chat.max_image_kb', 2048)),
                'mimetypes:'.$allowedMimeTypes,
                'mimes:'.$allowedExtensions,
            ],
            'meta' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => __('chat.validation.image_required'),
            'image.file' => __('chat.validation.image_file'),
            'image.max' => __('chat.validation.image_max'),
            'image.mimetypes' => __('chat.validation.image_mimetypes'),
            'image.mimes' => __('chat.validation.image_mimes'),
        ];
    }
}
