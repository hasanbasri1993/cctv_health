<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\DeviceChannel;
use App\Models\DeviceStorage;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $devices = Device::withCount([
            'channels',
            'alerts as active_alerts_count' => fn ($q) => $q->whereIn('status', ['active', 'acknowledged']),
        ])->orderBy('name')->get();

        $stats = Cache::remember('dashboard.stats', 30, function () use ($devices) {
            $offlineDeviceIds = $devices->where('status', 'offline')->pluck('id');

            return [
                'total_devices'         => $devices->count(),
                'online_devices'        => $devices->where('status', 'online')->count(),
                'offline_devices'       => $devices->where('status', 'offline')->count(),
                'active_alerts'         => Alert::whereIn('status', ['active', 'acknowledged'])->count(),

                // Status breakdown categories
                'video_loss'            => DeviceChannel::where('status', 'no_video')->count(),
                'comm_exception'        => $offlineDeviceIds->count(),
                'recording_exception'   => DeviceStorage::whereIn('health_status', ['fault', 'unknown'])
                                              ->whereHas('device', fn ($q) => $q->where('status', 'online'))
                                              ->count(),
                'storage_fault'         => DeviceStorage::where('health_status', 'fault')->count(),
            ];
        });

        return Inertia::render('Dashboard', [
            'devices' => $devices,
            'stats' => $stats,
        ]);
    }
}
