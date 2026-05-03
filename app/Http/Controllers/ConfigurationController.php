<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Configuration/Index', [
            'config' => [
                'polling_channel_interval' => env('POLLING_CHANNEL_INTERVAL', 1),
                'polling_storage_interval' => env('POLLING_STORAGE_INTERVAL', 5),
                'polling_device_interval' => env('POLLING_DEVICE_INTERVAL', 2),
                'notification_reminder_interval' => env('NOTIFICATION_REMINDER_INTERVAL', 60),
                'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN') ? '***configured***' : '',
                'telegram_chat_id' => env('TELEGRAM_CHAT_ID', ''),
                'mail_from_address' => env('MAIL_FROM_ADDRESS', ''),
                'alert_email_recipients' => env('ALERT_EMAIL_RECIPIENTS', ''),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'polling_channel_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'polling_storage_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'polling_device_interval' => ['required', 'integer', 'min:1', 'max:60'],
            'notification_reminder_interval' => ['required', 'integer', 'min:5', 'max:1440'],
            'telegram_bot_token' => ['nullable', 'string'],
            'telegram_chat_id' => ['nullable', 'string'],
            'mail_from_address' => ['nullable', 'email'],
            'alert_email_recipients' => ['nullable', 'string'],
        ]);

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $updates = [
            'POLLING_CHANNEL_INTERVAL' => $validated['polling_channel_interval'],
            'POLLING_STORAGE_INTERVAL' => $validated['polling_storage_interval'],
            'POLLING_DEVICE_INTERVAL' => $validated['polling_device_interval'],
            'NOTIFICATION_REMINDER_INTERVAL' => $validated['notification_reminder_interval'],
            'TELEGRAM_CHAT_ID' => $validated['telegram_chat_id'] ?? '',
            'ALERT_EMAIL_RECIPIENTS' => $validated['alert_email_recipients'] ?? '',
        ];

        if (! empty($validated['telegram_bot_token']) && $validated['telegram_bot_token'] !== '***configured***') {
            $updates['TELEGRAM_BOT_TOKEN'] = $validated['telegram_bot_token'];
        }

        if (! empty($validated['mail_from_address'])) {
            $updates['MAIL_FROM_ADDRESS'] = '"' . $validated['mail_from_address'] . '"';
        }

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
        Artisan::call('config:clear');

        return back()->with('success', 'Configuration saved.');
    }
}
