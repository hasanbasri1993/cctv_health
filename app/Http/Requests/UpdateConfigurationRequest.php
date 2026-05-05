<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
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
            'polling_channel_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'polling_storage_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'polling_device_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'notification_reminder_interval' => ['required', 'integer', 'min:5', 'max:1440'],
            'telegram_bot_token' => ['nullable', 'string'],
            'telegram_chat_ids' => ['nullable', 'string'],
            'telegram_message_thread_id' => ['nullable', 'string'],
            'mail_from_address' => ['nullable', 'email'],
            'alert_email_recipients' => ['nullable', 'string'],
        ];
    }
}
