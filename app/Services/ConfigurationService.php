<?php

namespace App\Services;

use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api as TelegramApi;

class ConfigurationService
{
    public function getConfigData(): array
    {
        return [
            'polling_channel_interval' => (int) SystemConfiguration::getValue('POLLING_CHANNEL_INTERVAL', env('POLLING_CHANNEL_INTERVAL', 1)),
            'polling_storage_interval' => (int) SystemConfiguration::getValue('POLLING_STORAGE_INTERVAL', env('POLLING_STORAGE_INTERVAL', 5)),
            'polling_device_interval' => (int) SystemConfiguration::getValue('POLLING_DEVICE_INTERVAL', env('POLLING_DEVICE_INTERVAL', 2)),
            'notification_reminder_interval' => (int) SystemConfiguration::getValue('NOTIFICATION_REMINDER_INTERVAL', env('NOTIFICATION_REMINDER_INTERVAL', 60)),
            'telegram_bot_token' => SystemConfiguration::getValue('TELEGRAM_BOT_TOKEN', env('TELEGRAM_BOT_TOKEN')) ? '***configured***' : '',
            'telegram_chat_ids' => SystemConfiguration::getValue('TELEGRAM_CHAT_ID', env('TELEGRAM_CHAT_ID', '')),
            'telegram_message_thread_id' => SystemConfiguration::getValue('TELEGRAM_MESSAGE_THREAD_ID', env('TELEGRAM_MESSAGE_THREAD_ID', '')),
            'mail_from_address' => SystemConfiguration::getValue('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', '')),
            'alert_email_recipients' => SystemConfiguration::getValue('ALERT_EMAIL_RECIPIENTS', env('ALERT_EMAIL_RECIPIENTS', '')),
        ];
    }

    public function updateConfiguration(array $validated): void
    {
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

            SystemConfiguration::setValue($key, $value);
        }

        if (! empty($validated['telegram_bot_token']) && $validated['telegram_bot_token'] !== '***configured***') {
            SystemConfiguration::setValue('TELEGRAM_BOT_TOKEN', $validated['telegram_bot_token']);
        }

        $mailValue = $validated['mail_from_address'] ?? '';
        SystemConfiguration::setValue('MAIL_FROM_ADDRESS', $mailValue);

        file_put_contents($envPath, $envContent);
        Artisan::call('config:clear');
    }

    public function testTelegram(array $validated): array
    {
        $token = $validated['telegram_bot_token'] ?? SystemConfiguration::getValue('TELEGRAM_BOT_TOKEN', config('services.telegram.bot_token'));
        $chatIdsInput = $validated['telegram_chat_ids'] ?? SystemConfiguration::getValue('TELEGRAM_CHAT_ID', config('services.telegram.chat_ids', []));
        $messageThreadId = $validated['telegram_message_thread_id'] ?? SystemConfiguration::getValue('TELEGRAM_MESSAGE_THREAD_ID', config('services.telegram.message_thread_id'));

        if (is_string($chatIdsInput)) {
            $chatIds = array_filter(explode(',', $chatIdsInput), fn($id) => trim($id) !== '');
        } else {
            $chatIds = $chatIdsInput;
        }

        if (! $token || empty($chatIds)) {
            return ['success' => false, 'message' => 'Telegram Bot Token and at least one Chat ID are required.'];
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

            return ['success' => true, 'message' => "Telegram test message sent successfully to {$sent} chat(s)!"];
        } catch (\Exception $e) {
            Log::error('Telegram test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Telegram test failed: ' . $e->getMessage()];
        }
    }

    public function testEmail(array $validated): array
    {
        $mailFrom = $validated['mail_from_address'] ?? SystemConfiguration::getValue('MAIL_FROM_ADDRESS', config('mail.from.address'));
        $recipients = $validated['alert_email_recipients'] ?? SystemConfiguration::getValue('ALERT_EMAIL_RECIPIENTS', config('monitoring.email_recipients', []));

        if (is_string($recipients)) {
            $recipients = array_filter(explode(',', $recipients), fn($email) => filter_var(trim($email), FILTER_VALIDATE_EMAIL));
        }

        if (! $mailFrom || $mailFrom === 'hello@example.com' || empty($recipients)) {
            return ['success' => false, 'message' => 'From Address and at least one valid recipient are required.'];
        }

        try {
            Config::set('mail.from.address', $mailFrom);

            $env = strtoupper(config('app.env', 'production'));

            Mail::raw("This is a test email from CCTV Monitor.\n\nEnvironment: {$env}\nTime: " . now()->format('Y-m-d H:i:s') . "\n\nIf you received this, your SMTP configuration is working correctly!", function ($message) use ($mailFrom, $recipients, $env) {
                $message->from($mailFrom)
                    ->to($recipients)
                    ->subject("[{$env}] [CCTV Monitor] Test Email");
            });

            return ['success' => true, 'message' => 'Test email sent successfully to ' . count($recipients) . ' recipient(s)!'];
        } catch (\Exception $e) {
            Log::error('Email test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Email test failed: ' . $e->getMessage()];
        }
    }
}
