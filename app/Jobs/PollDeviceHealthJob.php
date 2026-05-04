<?php

namespace App\Jobs;

use App\Contracts\HikvisionISAPIServiceInterface;
use App\Models\Device;
use App\Models\DeviceHealthLog;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollDeviceHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly Device $device) {}

    public function handle(HikvisionISAPIServiceInterface $isapi, AlertService $alertService): void
    {
        Log::info('Polling device health', ['device_id' => $this->device->id]);

        $response = $isapi->getDeviceHealth($this->device);

        $temperature = $this->device->storages()->value('temperature');

        DeviceHealthLog::create([
            'device_id' => $this->device->id,
            'status' => $response->success ? $response->status : 'offline',
            'response_time_ms' => $response->responseTimeMs,
            'error_message' => $response->error,
            'temperature' => $temperature,
        ]);

        $previousStatus = $this->device->status;
        $newStatus = $response->success ? 'online' : 'offline';

        $this->device->update([
            'status' => $newStatus,
            'last_seen_at' => $response->success ? now() : $this->device->last_seen_at,
            'firmware_version' => $response->firmwareVersion ?? $this->device->firmware_version,
            'model' => $response->model ?? $this->device->model,
        ]);

        if ($previousStatus !== $newStatus) {
            if ($newStatus === 'offline') {
                $alertService->createDeviceAlert($this->device->fresh(), 'offline');
            } elseif ($previousStatus === 'offline' && $newStatus === 'online') {
                $alertService->resolveDeviceAlerts($this->device->fresh());
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PollDeviceHealthJob failed', [
            'device_id' => $this->device->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
