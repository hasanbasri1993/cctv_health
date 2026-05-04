<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Alert::with(['device', 'acknowledgedBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        $alerts = $query->paginate(25)->withQueryString();
        $devices = Device::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts,
            'devices' => $devices,
            'filters' => $request->only(['status', 'severity', 'device_id']),
        ]);
    }

    public function acknowledge(Request $request, Alert $alert)
    {
        if (! $alert->isActive()) {
            return back()->with('error', 'Alert is not active.');
        }

        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Alert acknowledged.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Alert::with('device')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $filename = 'alerts-'.now()->format('Y-m-d').'.csv';

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
