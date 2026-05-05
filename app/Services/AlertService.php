<?php

namespace App\Services;

use App\Jobs\NotifyAlertJob;
use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceChannel;
use App\Models\DeviceStorage;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertService
{
    private int $reminderIntervalMinutes;

    public function __construct()
    {
        $this->reminderIntervalMinutes = (int) config('monitoring.notification_reminder_interval', 60);
    }

    public function createChannelAlert(DeviceChannel $channel, string $status): Alert
    {
        $existing = Alert::where('device_id', $channel->device_id)
            ->where('alertable_type', DeviceChannel::class)
            ->where('alertable_id', $channel->id)
            ->whereIn('status', ['active', 'acknowledged'])
            ->first();

        if ($existing) {
            $this->maybeQueueReminder($existing);

            return $existing;
        }

        $alert = Alert::create([
            'device_id' => $channel->device_id,
            'alertable_type' => DeviceChannel::class,
            'alertable_id' => $channel->id,
            'type' => 'channel_status',
            'severity' => 'warning',
            'title' => "Channel {$channel->channel_number} signal lost",
            'message' => "Channel {$channel->name} on device {$channel->device->name} has status: {$status}",
            'status' => 'active',
        ]);

        $this->queueNotification($alert);

        return $alert;
    }

    public function createStorageAlert(DeviceStorage $storage, string $health): Alert
    {
        $existing = Alert::where('device_id', $storage->device_id)
            ->where('alertable_type', DeviceStorage::class)
            ->where('alertable_id', $storage->id)
            ->whereIn('status', ['active', 'acknowledged'])
            ->first();

        if ($existing) {
            $this->maybeQueueReminder($existing);

            return $existing;
        }

        $severity = match ($health) {
            'fault' => 'critical',
            'full' => 'warning',
            default => 'warning',
        };

        $alert = Alert::create([
            'device_id' => $storage->device_id,
            'alertable_type' => DeviceStorage::class,
            'alertable_id' => $storage->id,
            'type' => 'storage_health',
            'severity' => $severity,
            'title' => "Storage {$storage->name} health issue",
            'message' => "Storage {$storage->name} on device {$storage->device->name} has health status: {$health}",
            'status' => 'active',
        ]);

        $this->queueNotification($alert);

        return $alert;
    }

    public function createDeviceAlert(Device $device, string $status): Alert
    {
        $existing = Alert::where('device_id', $device->id)
            ->where('alertable_type', Device::class)
            ->where('alertable_id', $device->id)
            ->where('type', 'device_offline')
            ->whereIn('status', ['active', 'acknowledged'])
            ->first();

        if ($existing) {
            $this->maybeQueueReminder($existing);

            return $existing;
        }

        $alert = Alert::create([
            'device_id' => $device->id,
            'alertable_type' => Device::class,
            'alertable_id' => $device->id,
            'type' => 'device_offline',
            'severity' => 'critical',
            'title' => "Device {$device->name} is offline",
            'message' => "Device {$device->name} ({$device->ip_address}) is not responding.",
            'status' => 'active',
        ]);

        $this->queueNotification($alert);

        return $alert;
    }

    public function resolveAlert(Alert $alert): bool
    {
        if ($alert->isResolved()) {
            return false;
        }

        return (bool) $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    public function resolveChannelAlerts(DeviceChannel $channel): void
    {
        Alert::where('alertable_type', DeviceChannel::class)
            ->where('alertable_id', $channel->id)
            ->whereIn('status', ['active', 'acknowledged'])
            ->each(fn (Alert $alert) => $this->resolveAlert($alert));
    }

    public function resolveStorageAlerts(DeviceStorage $storage): void
    {
        Alert::where('alertable_type', DeviceStorage::class)
            ->where('alertable_id', $storage->id)
            ->whereIn('status', ['active', 'acknowledged'])
            ->each(fn (Alert $alert) => $this->resolveAlert($alert));
    }

    public function resolveDeviceAlerts(Device $device): void
    {
        Alert::where('device_id', $device->id)
            ->where('type', 'device_offline')
            ->whereIn('status', ['active', 'acknowledged'])
            ->each(fn (Alert $alert) => $this->resolveAlert($alert));
    }

    public function shouldNotify(Alert $alert): bool
    {
        if ($alert->last_notified_at === null) {
            return true;
        }

        return $alert->last_notified_at->addMinutes($this->reminderIntervalMinutes)->isPast();
    }

    private function maybeQueueReminder(Alert $alert): void
    {
        if ($this->shouldNotify($alert)) {
            $this->queueNotification($alert);
        }
    }

    private function queueNotification(Alert $alert): void
    {
        dispatch(new NotifyAlertJob($alert));
    }

    public function getAlerts(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Alert::with(['device', 'acknowledgedBy'])->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function acknowledgeAlert(Alert $alert, User $user): bool
    {
        if (! $alert->isActive()) {
            return false;
        }

        return $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);
    }

    public function exportAlerts(array $filters): StreamedResponse
    {
        $query = Alert::with('device')->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        $filename = 'alerts-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Device', 'Type', 'Severity', 'Title', 'Status', 'Created At', 'Acknowledged At', 'Resolved At']);

            $query->chunk(500, function ($alerts) use ($handle) {
                foreach ($alerts as $alert) {
                    fputcsv($handle, [
                        $alert->id,
                        $alert->device->name ?? '',
                        $alert->type,
                        $alert->severity,
                        $alert->title,
                        $alert->status,
                        $alert->created_at->toDateTimeString(),
                        $alert->acknowledged_at?->toDateTimeString() ?? '',
                        $alert->resolved_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
