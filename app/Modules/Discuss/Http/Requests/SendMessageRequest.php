<?php

namespace App\Modules\Discuss\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required_without:attachments', 'nullable', 'string'],
            'attachments' => ['required_without:body', 'nullable', 'array'],
            'attachments.*' => ['url'],
            'reply_to_id' => ['nullable', 'string', 'max:26'],
            'type' => ['nullable', 'string', 'in:text,image,sticker,system'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
