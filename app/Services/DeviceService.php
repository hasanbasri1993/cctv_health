<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DeviceService
{
    public function getDevicesWithStats(): Collection
    {
        return Device::withCount(['channels', 'alerts' => fn ($q) => $q->whereIn('status', ['active', 'acknowledged'])])
            ->orderBy('name')
            ->get();
    }

    public function createDevice(array $data): Device
    {
        return Device::create($data);
    }

    public function updateDevice(Device $device, array $data): bool
    {
        return $device->update($data);
    }

    public function deleteDevice(Device $device): bool|null
    {
        return $device->delete();
    }

    public function getDeviceDetails(Device $device, int $perPage = 20): array
    {
        $device->load([
            'channels' => fn ($q) => $q->orderBy('channel_number'),
            'storages' => fn ($q) => $q->orderBy('storage_id'),
        ]);

        $alerts = $device->alerts()
            ->with('alertable')
            ->latest()
            ->limit(20)
            ->get();

        $healthLogs = $device->healthLogs()
            ->latest()
            ->paginate($perPage);

        $tempStats = $device->healthLogs()
            ->whereNotNull('temperature')
            ->selectRaw('MAX(temperature) as max, MIN(temperature) as min, ROUND(AVG(temperature), 1) as avg')
            ->first();

        $lastTemp = $device->healthLogs()
            ->whereNotNull('temperature')
            ->latest()
            ->value('temperature');

        return [
            'device' => $device,
            'channels' => $device->channels,
            'storages' => $device->storages,
            'alerts' => $alerts,
            'healthLogs' => $healthLogs,
            'tempStats' => $tempStats ? [
                'last' => $lastTemp,
                'min' => $tempStats->min,
                'max' => $tempStats->max,
                'avg' => $tempStats->avg,
            ] : null,
        ];
    }

    public function getHealthHistory(Device $device): Collection
    {
        return $device->healthLogs()
            ->select('status', 'response_time_ms', 'temperature', 'created_at')
            ->latest()
            ->limit(120)
            ->get()
            ->reverse()
            ->values();
    }
}
