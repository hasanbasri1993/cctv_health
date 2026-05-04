<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api as TelegramApi;

class NotificationService
{
    public function sendNotifications(Alert $alert): void
    {
        $sent = 0;
        $failed = 0;

        if (config('services.telegram.bot_token')) {
            try {
                $this->sendTelegramNotification($alert);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Telegram notification failed', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $recipients = config('monitoring.email_recipients', []);
        $mailFrom = config('mail.from.address');
        
        if (!empty($recipients) && $mailFrom && $mailFrom !== 'hello@example.com') {
            try {
                $this->sendEmailNotification($alert);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Email notification failed', [
                    'alert_id' => $alert->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Notifications sent', [
            'alert_id' => $alert->id,
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    public function sendTelegramNotification(Alert $alert): void
    {
        $token = config('services.telegram.bot_token');
        $chatIds = config('services.telegram.chat_ids', []);
        $messageThreadId = config('services.telegram.message_thread_id');

        if (! $token || empty($chatIds)) {
            Log::warning('Telegram credentials not configured');

            return;
        }

        $telegram = new TelegramApi($token);
        $message = $this->formatAlertMessage($alert);

        foreach ($chatIds as $chatId) {
            $chatId = trim($chatId);
            if (empty($chatId)) {
                continue;
            }

            $params = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            if ($messageThreadId) {
                $params['message_thread_id'] = $messageThreadId;
            }

            $telegram->sendMessage($params);
        }
    }

    public function sendEmailNotification(Alert $alert): void
    {
        $recipients = config('monitoring.email_recipients', []);

        if (empty($recipients)) {
            return;
        }

        Mail::send('emails.alert', ['alert' => $alert], function ($message) use ($alert, $recipients) {
            $env = strtoupper(config('app.env', 'production'));
            $message->to($recipients)
                ->subject("[{$env}] [{$alert->severity}] {$alert->title}");
        });
    }

    private function formatAlertMessage(Alert $alert): string
    {
        $severityEmoji = match ($alert->severity) {
            'critical' => '🔴',
            'warning' => '🟡',
            default => '🔵',
        };

        $env = strtoupper(config('app.env', 'production'));
        $envLabel = match ($env) {
            'PRODUCTION' => '🟢',
            'STAGING' => '🟠',
            'LOCAL' => '⚪',
            default => '🔷',
        };

        return "{$severityEmoji} <b>{$alert->title}</b>\n\n"
            ."{$alert->message}\n\n"
            ."Environment: {$envLabel} <b>{$env}</b>\n"
            ."Severity: <b>{$alert->severity}</b>\n"
            ."Time: {$alert->created_at->format('Y-m-d H:i:s')}\n"
            ."Device: {$alert->device->name}";
    }
}
