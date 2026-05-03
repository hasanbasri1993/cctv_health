<?php

namespace App\Jobs;

use App\Contracts\HikvisionISAPIServiceInterface;
use App\Models\Device;
use App\Models\DeviceChannel;
use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollChannelStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly Device $device) {}

    public function handle(HikvisionISAPIServiceInterface $isapi, AlertService $alertService): void
    {
        Log::info('Polling channel status', ['device_id' => $this->device->id]);

        $response = $isapi->getChannelStatus($this->device);

        if (! $response->success) {
            Log::warning('Channel status poll failed', [
                'device_id' => $this->device->id,
                'error' => $response->error,
            ]);

            return;
        }

        foreach ($response->channels as $channelData) {
            $channel = DeviceChannel::updateOrCreate(
                [
                    'device_id' => $this->device->id,
                    'channel_number' => $channelData['channel_number'],
                ],
                [
                    'name' => $channelData['name'],
                    'signal_quality' => $channelData['signal_quality'],
                ]
            );

            $previousStatus = $channel->status;
            $newStatus = $channelData['status'];

            if ($previousStatus !== $newStatus) {
                $channel->update([
                    'status' => $newStatus,
                    'last_status_change' => now(),
                ]);

                if ($newStatus !== 'ok') {
                    $alertService->createChannelAlert($channel->fresh(), $newStatus);
                } else {
                    $alertService->resolveChannelAlerts($channel->fresh());
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PollChannelStatusJob failed', [
            'device_id' => $this->device->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
