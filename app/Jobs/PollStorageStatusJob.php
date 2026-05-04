<?php

namespace App\Jobs;

use App\Contracts\HikvisionISAPIServiceInterface;
use App\Models\Device;
use App\Models\DeviceStorage;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollStorageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly Device $device) {}

    public function handle(HikvisionISAPIServiceInterface $isapi, AlertService $alertService): void
    {
        Log::info('Polling storage status', ['device_id' => $this->device->id]);

        $response = $isapi->getStorageStatus($this->device);

        if (! $response->success) {
            Log::warning('Storage status poll failed', [
                'device_id' => $this->device->id,
                'error' => $response->error,
            ]);

            return;
        }

        foreach ($response->storages as $storageData) {
            $storage = DeviceStorage::updateOrCreate(
                [
                    'device_id' => $this->device->id,
                    'storage_id' => $storageData['storage_id'],
                ],
                [
                    'name' => $storageData['name'],
                    'type' => $storageData['type'],
                    'capacity' => $storageData['capacity'],
                    'used_space' => $storageData['used_space'],
                    'temperature' => $storageData['temperature'],
                ]
            );

            $previousHealth = $storage->health_status;
            $newHealth = $storageData['health_status'];

            if ($previousHealth !== $newHealth) {
                $storage->update(['health_status' => $newHealth]);

                if (! in_array($newHealth, ['healthy', 'unknown'])) {
                    $alertService->createStorageAlert($storage->fresh(), $newHealth);
                } else {
                    $alertService->resolveStorageAlerts($storage->fresh());
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PollStorageStatusJob failed', [
            'device_id' => $this->device->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
