<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 60, 120, 300, 600];

    public function __construct(public readonly Alert $alert) {}

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->sendNotifications($this->alert);

        $this->alert->update(['last_notified_at' => now()]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('NotifyAlertJob failed', [
            'alert_id' => $this->alert->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
