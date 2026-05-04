<?php

namespace App\Http\Controllers;

use App\DTOs\ConnectionTestResult;
use App\Models\Device;
use App\Contracts\HikvisionISAPIServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function index(): Response
    {
        $devices = Device::withCount(['channels', 'alerts' => fn ($q) => $q->whereIn('status', ['active', 'acknowledged'])])
            ->orderBy('name')
            ->get();

        return Inertia::render('Devices/Index', [
            'devices' => $devices,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Devices/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        $device = Device::create($validated);

        return redirect()->route('devices.show', $device)->with('success', 'Device added successfully.');
    }

    public function show(Device $device): Response
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
            ->paginate(20);

        return Inertia::render('Devices/Show', [
            'device' => $device,
            'channels' => $device->channels,
            'storages' => $device->storages,
            'alerts' => $alerts,
            'healthLogs' => $healthLogs,
        ]);
    }

    public function edit(Device $device): Response
    {
        return Inertia::render('Devices/Edit', [
            'device' => $device->makeVisible('password'),
        ]);
    }

    public function update(Request $request, Device $device)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'max:255'];
        }

        $validated = $request->validate($rules);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $device->update($validated);

        return redirect()->route('devices.show', $device)->with('success', 'Device updated successfully.');
    }

    public function destroy(Device $device)
    {
        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Device deleted.');
    }

    public function testConnection(Device $device, HikvisionISAPIServiceInterface $isapi)
    {
        $result = $isapi->testConnection($device);

        return response()->json([
            'success' => $result->success,
            'response_time_ms' => $result->responseTimeMs,
            'device_info' => $result->deviceInfo,
            'error' => $result->error,
        ]);
    }

    public function healthHistory(Device $device)
    {
        $logs = $device->healthLogs()
            ->select('status', 'response_time_ms', 'created_at')
            ->latest()
            ->limit(120)
            ->get()
            ->reverse()
            ->values();

        return response()->json($logs);
    }
}
