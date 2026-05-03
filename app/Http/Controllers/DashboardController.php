<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
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
            return [
                'total_devices' => $devices->count(),
                'online_devices' => $devices->where('status', 'online')->count(),
                'offline_devices' => $devices->where('status', 'offline')->count(),
                'active_alerts' => Alert::whereIn('status', ['active', 'acknowledged'])->count(),
            ];
        });

        return Inertia::render('Dashboard', [
            'devices' => $devices,
            'stats' => $stats,
        ]);
    }
}
