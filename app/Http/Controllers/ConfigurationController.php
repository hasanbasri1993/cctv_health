<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Telegram\Bot\Api as TelegramApi;

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
                'telegram_chat_ids' => env('TELEGRAM_CHAT_ID', ''),
                'telegram_message_thread_id' => env('TELEGRAM_MESSAGE_THREAD_ID', ''),
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
            'telegram_chat_ids' => ['nullable', 'string'],
            'telegram_message_thread_id' => ['nullable', 'string'],
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
            'TELEGRAM_CHAT_ID' => $validated['telegram_chat_ids'] ?? '',
            'TELEGRAM_MESSAGE_THREAD_ID' => $validated['telegram_message_thread_id'] ?? '',
            'ALERT_EMAIL_RECIPIENTS' => $validated['alert_email_recipients'] ?? '',
        ];

        if (! empty($validated['telegram_bot_token']) && $validated['telegram_bot_token'] !== '***configured***') {
            $updates['TELEGRAM_BOT_TOKEN'] = $validated['telegram_bot_token'];
        }

        if (! empty($validated['mail_from_address'])) {
            $updates['MAIL_FROM_ADDRESS'] = '"'.$validated['mail_from_address'].'"';
        } elseif (isset($validated['mail_from_address'])) {
            $updates['MAIL_FROM_ADDRESS'] = '""';
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

    public function testTelegram(Request $request, NotificationService $notificationService)
    {
        $token = $request->input('telegram_bot_token') ?: config('services.telegram.bot_token');
        $chatIdsInput = $request->input('telegram_chat_ids') ?: config('services.telegram.chat_ids', []);
        $messageThreadId = $request->input('telegram_message_thread_id') ?: config('services.telegram.message_thread_id');

        if (is_string($chatIdsInput)) {
            $chatIds = array_filter(explode(',', $chatIdsInput), fn($id) => trim($id) !== '');
        } else {
            $chatIds = $chatIdsInput;
        }

        if (! $token || empty($chatIds)) {
            return back()->with('error', 'Telegram Bot Token and at least one Chat ID are required.');
        }

        try {
            Config::set('services.telegram.bot_token', $token);
            Config::set('services.telegram.chat_ids', $chatIds);

            $telegram = new TelegramApi($token);
            $env = strtoupper(config('app.env', 'production'));
            $text = "✅ <b>Test Successful</b>\n\nCCTV Monitor Telegram notification is working correctly!\n\nEnvironment: <b>{$env}</b>\nTime: " . now()->format('Y-m-d H:i:s');

            $sent = 0;
            foreach ($chatIds as $chatId) {
                $chatId = trim($chatId);
                if (empty($chatId)) {
                    continue;
                }

                $params = [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ];

                if ($messageThreadId) {
                    $params['message_thread_id'] = $messageThreadId;
                }

                $telegram->sendMessage($params);
                $sent++;
            }

            return back()->with('success', "Telegram test message sent successfully to {$sent} chat(s)!");
        } catch (\Exception $e) {
            Log::error('Telegram test failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Telegram test failed: ' . $e->getMessage());
        }
    }

    public function testEmail(Request $request)
    {
        $mailFrom = $request->input('mail_from_address') ?: config('mail.from.address');
        $recipients = $request->input('alert_email_recipients') ?: config('monitoring.email_recipients', []);

        if (is_string($recipients)) {
            $recipients = array_filter(explode(',', $recipients), fn($email) => filter_var(trim($email), FILTER_VALIDATE_EMAIL));
        }

        if (! $mailFrom || $mailFrom === 'hello@example.com' || empty($recipients)) {
            return back()->with('error', 'From Address and at least one valid recipient are required.');
        }

        try {
            Config::set('mail.from.address', $mailFrom);

            $env = strtoupper(config('app.env', 'production'));

            Mail::raw("This is a test email from CCTV Monitor.\n\nEnvironment: {$env}\nTime: " . now()->format('Y-m-d H:i:s') . "\n\nIf you received this, your SMTP configuration is working correctly!", function ($message) use ($mailFrom, $recipients, $env) {
                $message->from($mailFrom)
                    ->to($recipients)
                    ->subject("[{$env}] [CCTV Monitor] Test Email");
            });

            return back()->with('success', 'Test email sent successfully to ' . count($recipients) . ' recipient(s)!');
        } catch (\Exception $e) {
            Log::error('Email test failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Email test failed: ' . $e->getMessage());
        }
    }
}
